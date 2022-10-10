<?php

include 'dbconfig.php';

// INSERT SUBMITTED RATING & COMMENTS INTO DATABASE
if ((isset($_POST['rating']))&&(isset($_POST['comments'])))
{
    // CONNECT TO DATABASE
    include 'dbconfig.php';
    $db = connect_to_db($servername, $username, $password, $dbname);

    // CLEAN POST VARIABLES
    $rating = clean($_POST['rating']);
    $comments = clean($_POST['comments']);

  
    $days_since_last_review = days_since_last_review($db);
    
    // IF USER HAS ALREADY SUBMITTED A RATING
    if ($days_since_last_review)
    {
        // ALLOW RATING IF >= 30 DAYS
        if ($days_since_last_review >= 30.0)
        {
            insert_rating_and_comments($db, $rating, $comments);
            echo json_encode('success');
        }
        else
            echo json_encode($days_since_last_review);
    }
    // ALLOW RATING IF NEVER RATED BEFORE
    else
    {
        insert_rating_and_comments($db, $rating, $comments);
        echo json_encode('success');
    }

}



// HELPER FUNCTIONS

function connect_to_db($sn, $un, $pw, $dbn)
{
    // Create connection
    $conn = new mysqli($sn, $un, $pw, $dbn);
    // Check connection
    if ($conn->connect_error) {
        die("Database is currently unavailable: " . $conn->connect_error);
    }
    return $conn;
}

function clean($data)
{
    $data = stripslashes($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data);
    return $data;
}

function get_ip_address()
{
    $ip;
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}


function days_since_last_review($db)
{
    // QUERY DATABASE, RETURN DAYS SINCE LAST REVIEW (INT) IF ANY FOUND, ELSE RETURN FALSE

     // SQL with parameters
    $sql = "SELECT timestamp FROM ratings WHERE ip_address=? ORDER BY timestamp DESC";
    // prepare and bind
    $stmt = $db->prepare($sql); 
    $ip_address = get_ip_address();
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    // get the first result (most recent due to sorting in DESC order)
    $result = $stmt->get_result(); 
    // fetch data 
    if ($most_recent_review_date = $result->fetch_assoc())
    {  
        $time_since_last_review = time() - strtotime($most_recent_review_date['timestamp']);
        $days_since_last_review = $time_since_last_review / 86400.0;
        if ($days_since_last_review < 1.0)
            // return string to prevent 0 from being returned & evaluating to false accidentally
            return "less than 1";
        else
            // return integer representing days since last review
            return number_format($days_since_last_review);
    }
    else
        // no prior reviews, allow user to submit review
        return false;
}


function insert_rating_and_comments($db, $rating, $comments)
{
    // prepare and bind
    $stmt = $db->prepare("INSERT INTO ratings (ip_address, rating, comments) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $ip_address, $rating, $comments);
    // set parameters (rating & comments already set)
    $ip_address = get_ip_address();
    // execute
    $stmt->execute();
}

?>