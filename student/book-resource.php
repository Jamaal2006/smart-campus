<!DOCTYPE html>
<html>
<head>
    <title>Book a Resource</title>
</head>
<body>
    <h1>Book a Resource</h1>
    <form action="book-resource.php" method="post">
        <label for="resource">Select Resource:</label>
        <select id="resource" name="resource" required>
            <option value="1">Room A</option>
            <option value="2">Room B</option>
        </select>
        <button type="submit">Book</button>
    </form>
</body>
</html>