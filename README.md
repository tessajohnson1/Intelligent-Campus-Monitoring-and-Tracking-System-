#  Intelligent Campus Monitoring and Tracking System

## 📌 Project Overview

This mini project introduces an AI-powered facial recognition system designed to modernize student monitoring and bolster campus safety. Traditional attendance systems often suffer from manual inaccuracies and a lack of real-time insights. Our solution automates the identification of students at entry and exit points, ensuring higher precision and operational efficiency.
---

## 🧠 Technologies & Methodologies

* **Face Detection**: Implemented using **MTCNN (Multi-task Cascaded Convolutional Neural Network)** for reliable face detection under various conditions.
* **Face Recognition**: Powered by **ArcFace**, which generates robust facial embeddings for precise identification.
* **Data Handling**: Matches live face data with a secure student database (including registration numbers and photos).
* **Logging**: Stores entry and exit timestamps in **MySQL**, with logic to prevent duplicate entries in short intervals.

---

## 🖥️ Web Dashboard & Security Features

* Developed using **PHP** and **Bootstrap** for a responsive UI.
* Includes **role-based access control** for Admin, Head of Department (HOD), and Faculty.
* Modules for managing students, batches, faculty, and departments.
* **Real-time monitoring** with alert notifications for unexpected student movements.

---

## 🔔 Alerts & System Impact

* The system triggers alerts if students enter or leave without prior faculty approval.
* Helps reduce manual intervention and boosts campus safety.
* Designed for **scalability**, making it suitable for deployment in large educational institutions.

---

## 🗂️ System Architecture

The system operates through the following flow:

1. Detects face at entry/exit points using MTCNN
2. Generates face embeddings via ArcFace
3. Compares against the student database
4. Logs timestamp in MySQL
5. Sends alerts for unauthorized movements
6. Displays activity through the web dashboard

📎 [**Click here to view the System Workflow**](./System_workflow.pdf)

---

## ⚙️ Future Enhancements & Optimization Opportunities

* **Environmental Adaptability**: Improve recognition in low light, with obstructions (e.g., masks/glasses), and facial changes over time.
* **Performance Efficiency**: Optimize processing to handle larger crowds in real time.
* **Database Automation**: Add features for dynamic updates and validation of student data.
* **Enhanced Privacy & Security**: Use encryption and strict access policies to protect biometric data.
* **Ethical Use**: Implement user consent mechanisms and clear data usage policies for responsible monitoring.

---

## 💡 Skills & Learning Outcomes

This project strengthened our abilities in:

* Artificial Intelligence & Deep Learning
* Computer Vision (MTCNN, ArcFace)
* Web Development (PHP, Bootstrap, MySQL)
* Secure Data Management
* Real-world problem-solving for educational environments


