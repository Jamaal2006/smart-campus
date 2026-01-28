<!DOCTYPE html>
<html>
<head>
    <title>Add Resource</title>
</head>
<body>
    <h1>Add New Resource</h1>
    <form action="add-resource.php" method="post">
        <label for="name">Resource Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="description">Description:</label>
        <input type="text" id="description" name="description" required>
        <label for="quantity">Quantity:</label>
        <input type="number" id="quantity" name="quantity" required>
        <button type="submit">Add Resource</button>
    </form>
</body>
</html>