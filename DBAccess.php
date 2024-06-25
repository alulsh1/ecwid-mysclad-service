<?

function dbupdatesteps($step,$limit,$offset) {
    require (__DIR__.'/config.php');
    try {
          $conn = new PDO("mysql:host=".$host.";dbname=".$dbname, $username, $password); 
//          $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
          // set the PDO error mode to exception
          $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          $sql = 'UPDATE stepinfo SET step='.$step.',length='.$limit.',offset='.$offset;
//    echo '<pre>'; print_r($sql); echo '</pre>';

          // Prepare statement
          $stmt = $conn->prepare($sql);

          // execute the query
          $stmt->execute();

          // echo a message to say the UPDATE succeeded
//          echo $stmt->rowCount() . " records UPDATED successfully";
        } catch(PDOException $e) {
          echo $sql . "<br>" . $e->getMessage();
        }

        $conn = null;
}

function dbselect() {
    require (__DIR__.'/config.php');
    // Create connection
    $conn = new mysqli($host, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }
    
    $sql = 'SELECT * FROM stepinfo';
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}




