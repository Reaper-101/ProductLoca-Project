<?php
session_start();
include 'conn/conn.php'; // Include the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProductLoca - Main Customer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: #333;
        }

        .main {
            position: relative;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: #023047;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .icon  {
            max-width: 300px
           
        }
        .logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

        .navbar .menu ul {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        .navbar .menu ul li a {
            color: #fff;
            text-decoration: none;
            font-weight: 400;
            font-size: 16px;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .navbar .menu ul li a:hover {
            background: #ff7200;
            color: #fff;
        }

        .content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 100px 50px 50px; /* Adjusted for fixed navbar */
            max-width: 1400px;
            margin: 0 auto;
            flex: 1;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .text-content {
            flex: 1;
            max-width: 50%;
        }

        .text-content h1 {
            font-size: 48px;
            font-weight: 700;
            color: #023047;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .text-content h1 span {
            color: #ff7200;
            font-weight: 700;
        }

        .text-content .par {
            font-size: 18px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .text-content .cn {
            padding: 12px 25px;
            background: linear-gradient(90deg, #ff7200 0%, #ff8c00 100%);
            color: #fff;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 114, 0, 0.3);
        }

        .text-content .cn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 114, 0, 0.5);
        }

        .video-container {
            flex: 0 0 700px;
            margin-left: 30px;
        }

        .video-container video {
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .video-container video:hover {
            transform: scale(1.02);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content { flex-direction: column; padding: 80px 30px; }
            .text-content { max-width: 100%; text-align: center; margin-bottom: 30px; }
            .text-content h1 { font-size: 36px; }
            .text-content .par { font-size: 16px; }
            .video-container { flex: 0 0 100%; max-width: 600px; margin-left: 0; }
        }

        @media (max-width: 768px) {
            .navbar { flex-direction: column; padding: 10px 20px; }
            .navbar .menu ul { flex-direction: column; gap: 10px; margin-top: 10px; }
            .navbar .menu ul li a { font-size: 14px; padding: 6px 12px; }
            .content { padding: 70px 20px; }
            .text-content h1 { font-size: 28px; }
            .text-content .par { font-size: 14px; }
            .video-container { max-width: 100%; }
        }

        @media (max-width: 480px) {
            .navbar .icon .logo { font-size: 22px; }
            .content { padding: 60px 15px; }
            .text-content h1 { font-size: 24px; }
            .text-content .par { font-size: 12px; }
            .text-content .cn { padding: 10px 20px; font-size: 14px; }
            .video-container video { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="navbar">
            <div class="icon">
            <img src="image/ourlogo.png" alt="Logo 1" class="logo-img">
            </div>
            <div class="menu">
                <ul>
                    <li><a href="about.html">About</a></li>
                </ul>
            </div>
        </div> 

        <div class="content">
            <!-- Text content -->
            <div class="text-content">
                <h1>A Product Finder<br><span>System</span><br>Website</h1>
                <p class="par">ProductLoca provides a better shopping experience, a more convenient and hassle-free shopping tool as ProductLoca locates products for you within the department store.</p>
                <a href="index.php" class="cn">Click Here to Start Shopping!</a>
            </div>
            
            <!-- Video with looping -->
            <div class="video-container">
                <video controls loop muted autoplay>
                    <source src="video/video1.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </div>
</body>
</html>