<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hospital_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Create tables if not exist
$conn->query("CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    age INT,
    gender VARCHAR(10),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    specialty VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    appointment_date DATE,
    appointment_time TIME,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    amount DECIMAL(10,2),
    paid BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Simple login check (username=admin, password=admin123)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: hospital_management_system.php");
    exit;
}

if (!isset($_SESSION['user'])) {
    // Handle login
    $login_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $user = trim($_POST['username']);
        $pass = $_POST['password'];
        if ($user === 'admin' && $pass === 'admin123') {
            $_SESSION['user'] = $user;
            header("Location: hospital_management_system.php");
            exit;
        } else {
            $login_error = "Invalid username or password";
        }
    }
    ?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Hospital Management System</title>
    <style>
        body {font-family: Arial; background:#f0f2f5; display:flex; height:100vh; justify-content:center; align-items:center;}
        .login-box {background:white; padding:30px; border-radius:6px; box-shadow: 0 0 10px rgba(0,0,0,0.2); width: 300px;}
        input[type=text], input[type=password] {width:100%; padding:10px; margin:10px 0; box-sizing:border-box;}
        input[type=submit] {width:100%; padding:10px; background:#007bff; border:none; color:white; cursor:pointer; font-weight:bold;}
        .error {color:red; margin:10px 0;}
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <?php if ($login_error): ?>
            <div class="error"><?=$login_error?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" name="login" value="Login">
        </form>
        <p><small>Use <b>admin</b> / <b>admin123</b></small></p>
    </div>
</body>
</html>

<?php
    exit;
}

// If logged in, handle form submissions & display dashboard

$message = '';
// Add Patient
if (isset($_POST['add_patient'])) {
    $name = $conn->real_escape_string($_POST['patient_name']);
    $age = intval($_POST['patient_age']);
    $gender = $conn->real_escape_string($_POST['patient_gender']);
    $phone = $conn->real_escape_string($_POST['patient_phone']);
    $conn->query("INSERT INTO patients (name, age, gender, phone) VALUES ('$name', $age, '$gender', '$phone')");
    $message = "Patient added successfully.";
}
// Add Doctor
if (isset($_POST['add_doctor'])) {
    $name = $conn->real_escape_string($_POST['doctor_name']);
    $specialty = $conn->real_escape_string($_POST['doctor_specialty']);
    $phone = $conn->real_escape_string($_POST['doctor_phone']);
    $conn->query("INSERT INTO doctors (name, specialty, phone) VALUES ('$name', '$specialty', '$phone')");
    $message = "Doctor added successfully.";
}
// Add Appointment
if (isset($_POST['add_appointment'])) {
    $patient_id = intval($_POST['appointment_patient']);
    $doctor_id = intval($_POST['appointment_doctor']);
    $date = $conn->real_escape_string($_POST['appointment_date']);
    $time = $conn->real_escape_string($_POST['appointment_time']);
    $conn->query("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time) VALUES ($patient_id, $doctor_id, '$date', '$time')");
    $message = "Appointment added successfully.";
}
// Add Billing
if (isset($_POST['add_billing'])) {
    $patient_id = intval($_POST['billing_patient']);
    $amount = floatval($_POST['billing_amount']);
    $paid = isset($_POST['billing_paid']) ? 1 : 0;
    $conn->query("INSERT INTO billing (patient_id, amount, paid) VALUES ($patient_id, $amount, $paid)");
    $message = "Billing record added.";
}

// Fetch data for dropdowns and lists
$patients = $conn->query("SELECT id, name FROM patients ORDER BY name");
$doctors = $conn->query("SELECT id, name FROM doctors ORDER BY name");
$appointments = $conn->query("SELECT a.*, p.name as patient_name, d.name as doctor_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id ORDER BY a.appointment_date DESC");
$billings = $conn->query("SELECT b.*, p.name as patient_name FROM billing b JOIN patients p ON b.patient_id = p.id ORDER BY b.created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hospital Management System Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0; padding: 0;
        }
        nav {
            background: #007bff;
            padding: 10px 20px;
            color: white;
        }
        nav a {
            color: white;
            margin-right: 20px;
            text-decoration: none;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        h1, h2 {margin-top: 0;}
        form {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: 600;
        }
        input[type=text], input[type=number], input[type=date], input[type=time], select {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        input[type=submit] {
            margin-top: 15px;
            background: #007bff;
            border: none;
            color: white;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
        }
        input[type=submit]:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f4f4f4;
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .logout-link {
            float: right;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>

<nav>
    Hospital Management System
    <a href="?action=logout" class="logout-link">Logout</a>
</nav>

<div class="container">
    <h1>Dashboard</h1>

    <?php if ($message): ?>
        <div class="message"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>

    <!-- Add Patient -->
    <section>
        <h2>Add Patient</h2>
        <form method="post">
            <label>Name:
                <input type="text" name="patient_name" required>
            </label>
            <label>Age:
                <input type="number" name="patient_age" min="0" max="150" required>
            </label>
            <label>Gender:
                <select name="patient_gender" required>
                    <option value="">--Select Gender--</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </label>
            <label>Phone:
                <input type="text" name="patient_phone" required>
            </label>
            <input type="submit" name="add_patient" value="Add Patient">
        </form>
    </section>

    <!-- Add Doctor -->
    <section>
        <h2>Add Doctor</h2>
        <form method="post">
            <label>Name:
                <input type="text" name="doctor_name" required>
            </label>
            <label>Specialty:
                <input type="text" name="doctor_specialty" required>
            </label>
            <label>Phone:
                <input type="text" name="doctor_phone" required>
            </label>
            <input type="submit" name="add_doctor" value="Add Doctor">
        </form>
    </section>

    <!-- Add Appointment -->
    <section>
        <h2>Add Appointment</h2>
        <form method="post">
            <label>Patient:
                <select name="appointment_patient" required>
                    <option value="">--Select Patient--</option>
                    <?php while($p = $patients->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </label>
            <label>Doctor:
                <select name="appointment_doctor" required>
                    <option value="">--Select Doctor--</option>
                    <?php 
                    $doctors->data_seek(0);
                    while($d = $doctors->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </label>
            <label>Date:
                <input type="date" name="appointment_date" required>
            </label>
            <label>Time:
                <input type="time" name="appointment_time" required>
            </label>
            <input type="submit" name="add_appointment" value="Add Appointment">
        </form>
    </section>

    <!-- Add Billing -->
    <section>
        <h2>Add Billing</h2>
        <form method="post">
            <label>Patient:
                <select name="billing_patient" required>
                    <option value="">--Select Patient--</option>
                    <?php 
                    $patients->data_seek(0);
                    while($p = $patients->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </label>
            <label>Amount:
                <input type="number" step="0.01" name="billing_amount" required>
            </label>
            <label>
                <input type="checkbox" name="billing_paid"> Paid
            </label>
            <input type="submit" name="add_billing" value="Add Billing">
        </form>
    </section>

    <!-- Show Appointments -->
    <section>
        <h2>Appointments</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($a = $appointments->fetch_assoc()): ?>
                    <tr>
                        <td><?= $a['id'] ?></td>
                        <td><?= htmlspecialchars($a['patient_name']) ?></td>
                        <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                        <td><?= $a['appointment_date'] ?></td>
                        <td><?= $a['appointment_time'] ?></td>
                        <td><?= $a['status'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>

    <!-- Show Billing -->
    <section>
        <h2>Billing Records</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Patient</th><th>Amount</th><th>Paid</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php while($b = $billings->fetch_assoc()): ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><?= htmlspecialchars($b['patient_name']) ?></td>
                        <td><?= $b['amount'] ?></td>
                        <td><?= $b['paid'] ? 'Yes' : 'No' ?></td>
                        <td><?= $b['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>

</div>

</body>
</html>





















