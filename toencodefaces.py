import dlib
import cv2
import numpy as np
import os
from imutils import face_utils

# Initialize dlib's face detector, shape predictor, and face recognition model
detector = dlib.get_frontal_face_detector()
predictor = dlib.shape_predictor("shape_predictor_68_face_landmarks.dat")
face_recognizer = dlib.face_recognition_model_v1("dlib_face_recognition_resnet_model_v1.dat")

# Directory containing images of known individuals
known_faces_dir = "known_faces"

# Initialize lists to store encodings and names
known_face_encodings = []
known_face_names = []

# Loop over each person in the known_faces directory
for person_name in os.listdir(known_faces_dir):
    person_dir = os.path.join(known_faces_dir, person_name)
    
    # Loop over each image in the person's directory
    for image_name in os.listdir(person_dir):
        image_path = os.path.join(person_dir, image_name)
        image = cv2.imread(image_path)
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        
        # Detect faces
        faces = detector(gray, 0)
        
        # Loop over each detected face
        for face in faces:
            shape = predictor(gray, face)
            face_encoding = face_recognizer.compute_face_descriptor(image, shape)
            known_face_encodings.append(np.array(face_encoding))
            known_face_names.append(person_name)

# Save the encodings and names
np.save("known_face_encodings.npy", known_face_encodings)
np.save("known_face_names.npy", known_face_names)
print(" Saved known_face_encodings.npy and known_face_names.npy")
