<?php
include 'conn/conn.php'; // Include the database connection
session_start(); // Start the session

// Check if kiosk is set up
if (!isset($_SESSION['kioskID']) || !isset($_SESSION['kioskNum']) || !isset($_SESSION['kioskCode']) || !isset($_SESSION['business_id'])) {
    echo "<script>alert('Kiosk not set up. Please configure it first.'); window.location.href = 'admin/setup_kiosk.php';</script>";
    exit();
}

// Check if a product ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('No product selected for location.'); window.location.href = 'index.php';</script>";
    exit();
}

$kioskID = (int)$_SESSION['kioskID']; // Get kioskID from session
$businessID = (int)$_SESSION['business_id'];
$productID = intval($_GET['id']); // Get the product ID from URL
$search_query = isset($_GET['search_query']) ? $conn->real_escape_string($_GET['search_query']) : '';

// Fetch product details
$sql_product = "
    SELECT 
        p.ProdID, p.Prod_name, p.Prod_description, p.Price, p.Image AS ProdImage, p.Prod_color, p.Brand, p.Prod_type,
        s.StoreID, s.StoreBrandName, s.Image AS StoreImage
    FROM product p
    LEFT JOIN store s ON p.StoreID = s.StoreID
    WHERE p.ProdID = ? AND p.status = 'active' AND s.status = 'active'";
$stmt_product = $conn->prepare($sql_product);
if (!$stmt_product) {
    die("Prepare failed: " . $conn->error);
}
$stmt_product->bind_param("i", $productID);
$stmt_product->execute();
$result_product = $stmt_product->get_result();

if ($result_product->num_rows > 0) {
    $product = $result_product->fetch_assoc();
} else {
    echo "<script>alert('Product not found.'); window.location.href = 'index.php';</script>";
    exit();
}
$stmt_product->close();

// Fetch location and album details based on kioskID and StoreID
$storeID = $product['StoreID'];
$sql_location = "
    SELECT 
        l.LocID, l.LocName, l.FloorLevel,
        la.AlbumID, la.AlbumName, la.Description AS AlbumDescription,
        GROUP_CONCAT(DISTINCT CONCAT(li.Filename, ':', li.MapCode) SEPARATOR '|') AS FileDetails
    FROM location l
    LEFT JOIN location_albums la ON l.LocID = la.LocID
    LEFT JOIN locationimage li ON la.AlbumID = li.AlbumID
    WHERE l.KioskID = ? AND l.StoreID = ? AND l.status = 'active'
    GROUP BY l.LocID, l.LocName, l.FloorLevel, la.AlbumID, la.AlbumName, la.Description";
$stmt_location = $conn->prepare($sql_location);
if (!$stmt_location) {
    die("Prepare failed: " . $conn->error);
}
$stmt_location->bind_param("ii", $kioskID, $storeID);
$stmt_location->execute();
$result_location = $stmt_location->get_result();

$albumDetails = [];
if ($result_location->num_rows > 0) {
    while ($row = $result_location->fetch_assoc()) {
        $fileDetails = $row['FileDetails'] ? explode('|', $row['FileDetails']) : [];
        $row['Images'] = [];
        foreach ($fileDetails as $detail) {
            if ($detail) {
                list($filename, $mapCode) = explode(':', $detail, 2);
                $row['Images'][] = ['Filename' => $filename, 'MapCode' => $mapCode];
            }
        }
        unset($row['FileDetails']);
        $albumDetails[] = $row;
    }
} else {
    $albumDetails = [['LocName' => 'Unknown Location', 'FloorLevel' => 'N/A', 'AlbumName' => 'No Album', 'AlbumDescription' => '', 'Images' => []]];
}
$stmt_location->close();

// Calculate the number of maps to dynamically adjust their size
$mapCount = count(array_filter($albumDetails, fn($album) => !empty($album['Images'])));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Locate Product - <?php echo htmlspecialchars($product['Prod_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #1C2526 0%, #1B263B 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            color: #fff;
            overflow-x: hidden;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .container { 
            flex: 1; 
            width: 100%; 
            max-width: 1200px; 
            margin: 20px auto; 
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 140, 0, 0.2);
            padding: 20px; 
            display: flex; 
            flex-direction: row; 
            gap: 20px; 
            justify-content: space-between; 
            align-items: stretch;
        }
        h2 { 
            text-align: center; 
            color: #ff7200; 
            font-size: 2rem; 
            margin-bottom: 20px; 
        }
        h3 {
            color: #ff7200;
            margin-bottom: 10px;
        }
        .left-section { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
            min-width: 300px;
        }
        .product-details, .store-info { 
            width: 100%; 
        }
        .product-info { 
            display: flex; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 20px; 
        }
        .product-details img { 
            width: 15vw; 
            height: 15vw; 
            max-width: 150px; 
            max-height: 150px; 
            object-fit: cover; 
            border-radius: 10px; 
            border: 2px solid rgba(255, 140, 0, 0.3);
        }
        .store-info img { 
            width: 100%; 
            height: auto; 
            max-width: 400px; 
            max-height: 400px; 
            object-fit: cover; 
            border-radius: 10px; 
            border: 2px solid rgba(255, 140, 0, 0.3);
            display: block; 
            margin: 0 auto; 
        }
        .map-container { 
            flex: 2; 
            display: flex; 
            flex-direction: column; 
            overflow: hidden;
        }
        .map-row { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            justify-content: center; 
            padding-top: 20px;
            height: 100%;
            overflow: hidden;
        }
        .map-item { 
            flex: 1; 
            min-width: 300px; 
            max-width: <?php echo $mapCount > 0 ? (100 / $mapCount) . '%' : '100%'; ?>; 
            text-align: center; 
            cursor: pointer; 
        }
        .map-item img { 
            width: 100%; 
            height: auto; 
            max-height: 400px; 
            object-fit: contain; 
            border: 1px solid rgba(255, 140, 0, 0.3);
            border-radius: 10px; 
            transition: transform 0.2s; 
        }
        .map-item img:hover { 
            transform: scale(1.05); 
        }
        .map-item p { 
            margin-top: 10px; 
            font-size: 0.9rem; 
        }
        .back-button { 
            margin-top: 20px; 
            text-align: center; 
        }
        .back-button a { 
            background: linear-gradient(90deg, #1B263B 0%, #ff7200 100%);
            color: white; 
            padding: 15px 30px; 
            text-decoration: none; 
            border-radius: 25px; 
            font-size: 1.2rem;
            transition: transform 0.1s ease;
        }
        .back-button a:hover { 
            transform: scale(1.02);
        }
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0, 0, 0, 0.8); 
            justify-content: center; 
            align-items: center; 
            z-index: 1000; 
            overflow-y: auto; 
        }
        .modal-content { 
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 20px; 
            border-radius: 10px; 
            max-width: 95%;
            max-height: 90vh; 
            overflow-x: auto; 
            text-align: center; 
            border: 1px solid rgba(255, 140, 0, 0.2);
        }
        .modal-content h3 { 
            color: #ff7200; 
            margin-bottom: 15px; 
        }
        .modal-description { 
            color: #fff; 
            font-size: 1rem; 
            margin-bottom: 20px; 
            text-align: left; 
            max-width: 800px; 
            margin-left: auto; 
            margin-right: auto; 
        }
        .album-gallery { 
            display: flex; 
            flex-direction: row; 
            flex-wrap: nowrap; 
            gap: 15px; 
            justify-content: flex-start; 
            overflow-x: auto; 
            padding-bottom: 10px; 
        }
        .album-gallery .image-wrapper { 
            text-align: center; 
        }
        .album-gallery img { 
            width: auto; 
            max-width: 600px; 
            max-height: 500px; 
            object-fit: contain; 
            border-radius: 5px; 
            cursor: pointer; 
            border: 2px solid rgba(255, 140, 0, 0.3);
        }
        .album-gallery p { 
            color: #fff; 
            font-size: 0.9rem; 
            margin-top: 5px; 
        }
        .modal-close { 
            position: absolute; 
            top: 20px; 
            right: 20px; 
            color: #fff; 
            font-size: 2rem; 
            cursor: pointer; 
        }
        @media (max-width: 1024px) {
            .container { flex-direction: column; align-items: center; }
            .left-section { width: 100%; max-width: 500px; }
            .map-container { width: 100%; }
            .map-item { min-width: 250px; max-width: 100%; }
            .map-item img { max-height: 300px; }
            .album-gallery img { max-width: 400px; max-height: 350px; }
        }
        @media (max-width: 768px) {
            h2 { font-size: 1.5rem; }
            .product-details img { width: 20vw; height: 20vw; }
            .store-info img { width: 25vw; height: 25vw; }
            .map-item { min-width: 200px; }
            .map-item img { max-height: 200px; }
            .back-button a { padding: 10px 20px; font-size: 1rem; }
            .album-gallery img { max-width: 300px; max-height: 250px; }
        }
        @media (max-width: 480px) {
            h2 { font-size: 1.2rem; }
            .product-details img { width: 25vw; height: 25vw; }
            .store-info img { width: 30vw; height: 30vw; }
            .map-item { min-width: 150px; }
            .map-item img { max-height: 150px; }
            .album-gallery img { max-width: 200px; max-height: 150px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="left-section">
        <h2>Product Location - <?php echo htmlspecialchars($albumDetails[0]['LocName'] ?? 'Unknown Location'); ?></h2>

        <div class="product-details">
            <div class="product-info">
                <img src="uploads/<?php echo htmlspecialchars($product['ProdImage'] ?? 'default-product.jpg'); ?>" alt="Product Image">
                <div>
                    <h3><?php echo htmlspecialchars($product['Prod_name']); ?></h3>
                    <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['Brand']); ?></p>
                    <p><strong>Color:</strong> <?php echo htmlspecialchars($product['Prod_color']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($product['Prod_type'] ?? 'N/A'); ?></p>
                    <p><strong>Price:</strong> ₱<?php echo htmlspecialchars($product['Price']); ?></p>
                    <p><?php echo htmlspecialchars($product['Prod_description']); ?></p>
                </div>
            </div>
        </div>

        <div class="store-info">
            <h3>Target Store: <?php echo htmlspecialchars($product['StoreBrandName']); ?></h3>
            <img src="uploads/<?php echo htmlspecialchars($product['StoreImage'] ?? 'default-store.jpg'); ?>" alt="Store Image">
        </div>

        <div class="back-button">
            <a href="product_details.php?id=<?php echo $product['ProdID']; ?>&search_query=<?php echo urlencode($search_query); ?>">Back to Product Details</a>
        </div>
    </div>

    <div class="map-container">
        <div class="map-row">
            <?php foreach ($albumDetails as $album): ?>
                <?php if (!empty($album['Images'])): ?>
                    <div class="map-item" onclick="openAlbum(<?php echo $album['AlbumID']; ?>, '<?php echo htmlspecialchars(json_encode($album['Images'])); ?>', '<?php echo htmlspecialchars($album['AlbumName']); ?>', '<?php echo htmlspecialchars($album['LocName']); ?>', '<?php echo htmlspecialchars($album['FloorLevel']); ?>', '<?php echo htmlspecialchars($album['AlbumDescription']); ?>')">
                        <img src="uploads/floorplans/<?php echo htmlspecialchars($album['Images'][0]['Filename'] ?? 'default-map.jpg'); ?>" alt="Album Preview">
                        <p><strong><?php echo htmlspecialchars($album['AlbumName']); ?></strong><br>
                        Location: <?php echo htmlspecialchars($album['LocName']); ?><br>
                        Floor: <?php echo htmlspecialchars($album['FloorLevel']); ?><br>
                        <!-- Map Code: <?php echo htmlspecialchars($album['Images'][0]['MapCode'] ?? 'N/A'); ?></p> -->
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php if (empty(array_filter($albumDetails, fn($album) => !empty($album['Images'])))): ?>
            <p>No map albums available for this store location.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for album view -->
<div id="mapModal" class="modal">
    <span class="modal-close" onclick="closeModal()">×</span>
    <div id="modalContent" class="modal-content"></div>
</div>

<script>
    function openAlbum(albumID, imagesJson, albumName, locName, floorLevel, albumDescription) {
        const modal = document.getElementById('mapModal');
        const modalContent = document.getElementById('modalContent');
        let images;
        try {
            images = JSON.parse(imagesJson);
        } catch (e) {
            console.error('Error parsing images JSON:', e);
            images = [];
        }

        if (!Array.isArray(images) || images.length === 0) {
            modalContent.innerHTML = '<h3>No images available</h3>';
        } else {
            let html = `<h3>${albumName} - ${locName} (Floor: ${floorLevel})</h3>`;
            html += `<p class="modal-description">${albumDescription || 'No description available'}</p>`;
            html += '<div class="album-gallery">';
            images.forEach((image) => {
                html += `
                    <div class="image-wrapper">
                        <img src="uploads/floorplans/${image.Filename}" alt="${image.MapCode} Image" onerror="this.src='uploads/floorplans/default-map.jpg'">
                       
                    </div>`;
            });
            html += '</div>';
            modalContent.innerHTML = html;
        }
        
        modal.style.display = 'flex';
    }

    function closeModal() {
        const modal = document.getElementById('mapModal');
        modal.style.display = 'none';
    }

    document.getElementById('mapModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>
</body>
</html>