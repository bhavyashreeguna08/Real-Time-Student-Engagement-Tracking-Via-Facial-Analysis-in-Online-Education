import cv2
import dlib
import numpy as np
import mysql.connector
from datetime import datetime, timedelta
from imutils import face_utils
from scipy.spatial import distance as dist
import csv

# Initialize dlib's face detector, shape predictor, and face recognition model
face_detector = dlib.get_frontal_face_detector()
shape_predictor = dlib.shape_predictor("shape_predictor_68_face_landmarks.dat")
face_recognizer = dlib.face_recognition_model_v1("dlib_face_recognition_resnet_model_v1.dat")

# Load known face encodings and names
known_face_encodings = np.load("known_face_encodings.npy", allow_pickle=True)
known_face_names = np.load("known_face_names.npy", allow_pickle=True)

# Dictionary to track login time, logout time, and engagement score
student_sessions = {}

# Dictionary to track last seen times
last_seen_times = {}

# Duration to consider as logged out (in seconds)
LOGOUT_THRESHOLD = 5

# Function to calculate eye aspect ratio
def eye_aspect_ratio(eye):
    A = dist.euclidean(eye[1], eye[5])
    B = dist.euclidean(eye[2], eye[4])
    C = dist.euclidean(eye[0], eye[3])
    ear = (A + B) / (2.0 * C)
    return ear

# Function to calculate mouth aspect ratio
def mouth_aspect_ratio(mouth):
    A = dist.euclidean(mouth[13], mouth[19])
    B = dist.euclidean(mouth[14], mouth[18])
    C = dist.euclidean(mouth[15], mouth[17])
    D = dist.euclidean(mouth[12], mouth[16])
    mar = (A + B + C) / (3.0 * D)
    return mar

# Function to detect gaze ratio
def detect_gaze_ratio(eye_points, landmarks, frame):
    left_eye_region = np.array([(landmarks.part(point).x, landmarks.part(point).y) for point in eye_points])
    min_x = np.min(left_eye_region[:, 0])
    max_x = np.max(left_eye_region[:, 0])
    min_y = np.min(left_eye_region[:, 1])
    max_y = np.max(left_eye_region[:, 1])

    eye = frame[min_y:max_y, min_x:max_x]
    gray_eye = cv2.cvtColor(eye, cv2.COLOR_BGR2GRAY)
    _, threshold_eye = cv2.threshold(gray_eye, 70, 255, cv2.THRESH_BINARY)
    height, width = threshold_eye.shape
    left_side_threshold = threshold_eye[0:height, 0:int(width / 2)]
    left_side_white = cv2.countNonZero(left_side_threshold)
    right_side_threshold = threshold_eye[0:height, int(width / 2):width]
    right_side_white = cv2.countNonZero(right_side_threshold)

    if right_side_white == 0:
        gaze_ratio = 1
    else:
        gaze_ratio = left_side_white / right_side_white
    return gaze_ratio

# Function to calculate engagement state
def calculate_engagement_state(landmarks, frame):
    left_eye = face_utils.shape_to_np(landmarks)[36:42]
    right_eye = face_utils.shape_to_np(landmarks)[42:48]
    mouth = face_utils.shape_to_np(landmarks)[48:68]

    ear = (eye_aspect_ratio(left_eye) + eye_aspect_ratio(right_eye)) / 2.0
    mar = mouth_aspect_ratio(mouth)
    gaze_ratio_left = detect_gaze_ratio([36, 37, 38, 39, 40, 41], landmarks, frame)
    gaze_ratio_right = detect_gaze_ratio([42, 43, 44, 45, 46, 47], landmarks, frame)

    if mar > 0.6:
        return "Not Engaged", 3
    elif ear < 0.2 and mar < 0.5:
        return "Barely Engaged", 4
    elif gaze_ratio_left < 0.5 or gaze_ratio_right > 2.0:
        return "Distracted/Confused", 5
    else:
        return "Highly Engaged", 9

# Function to detect faces and update attendance data
def detect_faces_and_update_attendance(frame, known_face_encodings, known_face_names, face_detector, predictor, face_recognizer, db_conn):
    cursor = db_conn.cursor()
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    faces = face_detector(gray, 1)
    
    current_time = datetime.now()
    
    detected_names = []

    for face in faces:
        shape = predictor(gray, face)
        face_encoding = face_recognizer.compute_face_descriptor(frame, shape)
        face_encoding = np.array(face_encoding)
        
        # Compute distances and find the best match
        distances = np.linalg.norm(known_face_encodings - face_encoding, axis=1)
        min_distance_index = np.argmin(distances)
        
        if distances[min_distance_index] < 0.6:  # You can adjust the threshold
            name = known_face_names[min_distance_index]
            name = str(name)
            detected_names.append(name)
            
            if name not in student_sessions:
                student_sessions[name] = {
                    'login_time': current_time,
                    'logout_time': None,
                    'engagement_scores': []
                }
                
                # Insert new attendance record
                cursor.execute("INSERT INTO attendance (name, login_time, logout_time, avg_engagement) VALUES (%s, %s, NULL, 0)",
                               (name, current_time))
                db_conn.commit()
            
            session_data = student_sessions[name]
            
            # Update engagement state and score
            engagement_state, engagement_score = calculate_engagement_state(shape, frame)
            session_data['engagement_scores'].append(engagement_score)
            
            # Update last seen time
            last_seen_times[name] = current_time
            
            cv2.putText(frame, name, (face.left(), face.top() - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 2)
            cv2.rectangle(frame, (face.left(), face.top()), (face.right(), face.bottom()), (0, 255, 0), 2)
            cv2.putText(frame, engagement_state, (face.left(), face.bottom() + 20), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 0, 0), 2)

    # Check for logouts
    for name in list(student_sessions.keys()):
        if name not in detected_names:
            last_seen = last_seen_times.get(name, current_time)
            if current_time - last_seen > timedelta(seconds=LOGOUT_THRESHOLD):
                if student_sessions[name]['logout_time'] is None:
                    student_sessions[name]['logout_time'] = current_time
                    cursor.execute("UPDATE attendance SET logout_time = %s WHERE name = %s AND logout_time IS NULL",
                                   (current_time, name))
                    db_conn.commit()
    
    return frame

def write_session_data_to_csv():
    with open('session_data.csv', mode='w', newline='') as file:
        writer = csv.writer(file)
        writer.writerow(['Name', 'First Login Time', 'Last Logout Time', 'Average Engagement Score'])
        
        for name, session_data in student_sessions.items():
            login_time = session_data['login_time'].strftime("%Y-%m-%d %H:%M:%S")
            logout_time = session_data['logout_time'].strftime("%Y-%m-%d %H:%M:%S") if session_data['logout_time'] else ""
            avg_engagement = sum(session_data['engagement_scores']) / len(session_data['engagement_scores']) if session_data['engagement_scores'] else 0
            
            writer.writerow([name, login_time, logout_time, avg_engagement])

def main():
    # Replace sensitive information with placeholder
    db_conn = mysql.connector.connect(
        host="localhost",  # Database host
        user="root",  # Database user
        password="",  # Database password (leave empty or add securely)
        database="attendance_db"  # Database name
    )
    
    video_capture = cv2.VideoCapture(0)  # Use webcam as video input

    while True:
        ret, frame = video_capture.read()
        if not ret:
            break

        # Process the frame for face detection and attendance tracking
        frame = detect_faces_and_update_attendance(frame, known_face_encodings, known_face_names, face_detector, shape_predictor, face_recognizer, db_conn)
        
        # Display the frame with overlayed data
        cv2.imshow("Attendance and Engagement Tracking", frame)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    # Write session data to CSV before closing
    write_session_data_to_csv()
    print("Done")

    # Release resources
    video_capture.release()
    cv2.destroyAllWindows()
    db_conn.close()

if __name__ == "__main__":
    main()
