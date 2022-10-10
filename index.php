<?php
    // CONNECT TO DATABASE
    include 'dbconfig.php';
    $db = connect_to_db($servername, $username, $password, $dbname);

    // CAPTURE VISIT DATA
    capture_visit_data($db);

    // REDIRECT TO RATINGS SUMMARY PAGE IF USER (IP ADDRESS) ALREADY LEFT RATING (IN DATABASE)
    $days_since_last_review = days_since_last_review($db);
    
    // IF USER HAS ALREADY SUBMITTED A RATING
    if ($days_since_last_review)
    {
        // IF LESS THAN 30 DAYS AGO
        if ($days_since_last_review < 30.0)
            exit("You already left a review " . $days_since_last_review . " day(s) ago. Please wait 30 days between reviews.");
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

    function capture_visit_data($db)
    {
        // prepare and bind
        $stmt = $db->prepare("INSERT INTO visits (ip_address, get_string) VALUES (?, ?)");
        $stmt->bind_param("ss", $ip_address, $get_string);
        // set parameters / clean data
        $ip_address = get_ip_address();
        $get_string = json_encode($_GET);
        // execute
        $stmt->execute();
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

?>

<!doctype html>
<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Latest compiled and minified CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Latest compiled JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

        
        <title>Rate My Driving</title>

    </head>
    <body class="bg-black">

        <div class="container-fluid p-2" style="background-image: url('https://cdn.pixabay.com/photo/2016/07/13/01/50/sign-1513493_960_720.png')"></div>
 
        <div class="container-fluid p-1 bg-warning text-black text-center">
            <h1>How's My Driving?</h1>
        </div>
        
        <div class="container-fluid p-2" style="background-image: url('https://cdn.pixabay.com/photo/2016/07/13/01/50/sign-1513493_960_720.png')"></div>

        <div class="container my-5 p-0">

            <div class="row my-3">
                <div class="col-12">
                    <div class="card border-warning rounded-6 mx-3">
                        <div class="card-header bg-warning text-center"></div> <!-- py-0 -->
                        <div class="card-body">

                            <div class="row border-black">

                                <div class="col text-center border-3">
                                    
                                    <div class="btn-group" role="group" id="button_group"> <!--  border border-3 border-warning rounded-3 -->
                                        <div class="btn text-white bg-secondary btn-outline-black">
                                            Rating:
                                        </div>

                                        <input type="checkbox" class="btn-check" id="1" autocomplete="off">
                                        <label class="btn btn-outline-warning" for="1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-car-front" viewBox="0 0 16 16">
                                                <path d="M4 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2H6ZM4.862 4.276 3.906 6.19a.51.51 0 0 0 .497.731c.91-.073 2.35-.17 3.597-.17 1.247 0 2.688.097 3.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 10.691 4H5.309a.5.5 0 0 0-.447.276Z"/>
                                                <path fill-rule="evenodd" d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679c.033.161.049.325.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.807.807 0 0 0 .381-.404l.792-1.848ZM4.82 3a1.5 1.5 0 0 0-1.379.91l-.792 1.847a1.8 1.8 0 0 1-.853.904.807.807 0 0 0-.43.564L1.03 8.904a1.5 1.5 0 0 0-.03.294v.413c0 .796.62 1.448 1.408 1.484 1.555.07 3.786.155 5.592.155 1.806 0 4.037-.084 5.592-.155A1.479 1.479 0 0 0 15 9.611v-.413c0-.099-.01-.197-.03-.294l-.335-1.68a.807.807 0 0 0-.43-.563 1.807 1.807 0 0 1-.853-.904l-.792-1.848A1.5 1.5 0 0 0 11.18 3H4.82Z"/>
                                            </svg>
                                        </label>
                                        
                                        <input type="checkbox" class="btn-check" id="2" autocomplete="off">
                                        <label class="btn btn-outline-warning" for="2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-car-front" viewBox="0 0 16 16">
                                                <path d="M4 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2H6ZM4.862 4.276 3.906 6.19a.51.51 0 0 0 .497.731c.91-.073 2.35-.17 3.597-.17 1.247 0 2.688.097 3.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 10.691 4H5.309a.5.5 0 0 0-.447.276Z"/>
                                                <path fill-rule="evenodd" d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679c.033.161.049.325.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.807.807 0 0 0 .381-.404l.792-1.848ZM4.82 3a1.5 1.5 0 0 0-1.379.91l-.792 1.847a1.8 1.8 0 0 1-.853.904.807.807 0 0 0-.43.564L1.03 8.904a1.5 1.5 0 0 0-.03.294v.413c0 .796.62 1.448 1.408 1.484 1.555.07 3.786.155 5.592.155 1.806 0 4.037-.084 5.592-.155A1.479 1.479 0 0 0 15 9.611v-.413c0-.099-.01-.197-.03-.294l-.335-1.68a.807.807 0 0 0-.43-.563 1.807 1.807 0 0 1-.853-.904l-.792-1.848A1.5 1.5 0 0 0 11.18 3H4.82Z"/>
                                            </svg>
                                        </label>

                                        <input type="checkbox" class="btn-check" id="3" autocomplete="off">
                                        <label class="btn btn-outline-warning" for="3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-car-front" viewBox="0 0 16 16">
                                                <path d="M4 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2H6ZM4.862 4.276 3.906 6.19a.51.51 0 0 0 .497.731c.91-.073 2.35-.17 3.597-.17 1.247 0 2.688.097 3.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 10.691 4H5.309a.5.5 0 0 0-.447.276Z"/>
                                                <path fill-rule="evenodd" d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679c.033.161.049.325.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.807.807 0 0 0 .381-.404l.792-1.848ZM4.82 3a1.5 1.5 0 0 0-1.379.91l-.792 1.847a1.8 1.8 0 0 1-.853.904.807.807 0 0 0-.43.564L1.03 8.904a1.5 1.5 0 0 0-.03.294v.413c0 .796.62 1.448 1.408 1.484 1.555.07 3.786.155 5.592.155 1.806 0 4.037-.084 5.592-.155A1.479 1.479 0 0 0 15 9.611v-.413c0-.099-.01-.197-.03-.294l-.335-1.68a.807.807 0 0 0-.43-.563 1.807 1.807 0 0 1-.853-.904l-.792-1.848A1.5 1.5 0 0 0 11.18 3H4.82Z"/>
                                            </svg>
                                        </label>

                                        <input type="checkbox" class="btn-check" id="4" autocomplete="off">
                                        <label class="btn btn-outline-warning" for="4">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-car-front" viewBox="0 0 16 16">
                                                <path d="M4 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2H6ZM4.862 4.276 3.906 6.19a.51.51 0 0 0 .497.731c.91-.073 2.35-.17 3.597-.17 1.247 0 2.688.097 3.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 10.691 4H5.309a.5.5 0 0 0-.447.276Z"/>
                                                <path fill-rule="evenodd" d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679c.033.161.049.325.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.807.807 0 0 0 .381-.404l.792-1.848ZM4.82 3a1.5 1.5 0 0 0-1.379.91l-.792 1.847a1.8 1.8 0 0 1-.853.904.807.807 0 0 0-.43.564L1.03 8.904a1.5 1.5 0 0 0-.03.294v.413c0 .796.62 1.448 1.408 1.484 1.555.07 3.786.155 5.592.155 1.806 0 4.037-.084 5.592-.155A1.479 1.479 0 0 0 15 9.611v-.413c0-.099-.01-.197-.03-.294l-.335-1.68a.807.807 0 0 0-.43-.563 1.807 1.807 0 0 1-.853-.904l-.792-1.848A1.5 1.5 0 0 0 11.18 3H4.82Z"/>
                                            </svg>
                                        </label>

                                        <input type="checkbox" class="btn-check" id="5" autocomplete="off">
                                        <label class="btn btn-outline-warning" for="5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-car-front" viewBox="0 0 16 16">
                                                <path d="M4 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm10 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2H6ZM4.862 4.276 3.906 6.19a.51.51 0 0 0 .497.731c.91-.073 2.35-.17 3.597-.17 1.247 0 2.688.097 3.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 10.691 4H5.309a.5.5 0 0 0-.447.276Z"/>
                                                <path fill-rule="evenodd" d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679c.033.161.049.325.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.807.807 0 0 0 .381-.404l.792-1.848ZM4.82 3a1.5 1.5 0 0 0-1.379.91l-.792 1.847a1.8 1.8 0 0 1-.853.904.807.807 0 0 0-.43.564L1.03 8.904a1.5 1.5 0 0 0-.03.294v.413c0 .796.62 1.448 1.408 1.484 1.555.07 3.786.155 5.592.155 1.806 0 4.037-.084 5.592-.155A1.479 1.479 0 0 0 15 9.611v-.413c0-.099-.01-.197-.03-.294l-.335-1.68a.807.807 0 0 0-.43-.563 1.807 1.807 0 0 1-.853-.904l-.792-1.848A1.5 1.5 0 0 0 11.18 3H4.82Z"/>
                                            </svg>
                                        </label>

                                        <div class="btn text-white bg-secondary btn-outline-black" id="rating_display">
                                            0/5
                                        </div>

                                    </div> <!-- end button group -->

                                </div>   <!-- col end -->
                                

                            </div> <!-- row end -->

                            <input type='hidden' id='rating' value='0'>
                            
                            <div class="row border-secondary border-3 text-center">
                                <div class="col-sm-2 col-0"></div>
                                <div class="col-sm-8 col-12">
                                    <div class="card rounded-6 m-3">
                                        <div class="card-header bg-secondary text-white p-0"><label for="comments">Comments:</label></div>
                                        <div class="card-body bg-warning">
                                            <div class="form-group shadow-textarea">
                                                <textarea id="comments" class="form-control z-depth-1" id="comments" rows="3" placeholder="Share your experience..."></textarea>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-secondary"></div>
                                    </div>
                                </div>
                                <div class="col-sm-2 col-0"></div>
                            </div>

                            <div class="row border-secondary border-3 text-center">
                                <div class="col-sm-3 col-0"></div>
                                <div class="col-sm-6 col-12">
                                    <button id="submit" type="button" class="btn bg-secondary text-white w-100">Submit</button>
                                </div>
                                <div class="col-sm-3 col-0"></div>
                            </div>

                        </div> <!-- card-body end -->
                        <div class="card-footer bg-warning"></div>
                    </div>
                </div>

            </div>



            <div class="row text-center">
                    <p class="text-white">Copyright 2022 howsmydriving.info</p>
            </div>

            <!-- prevents back button allowing multiple reviews -->
            <!-- source: https://stackoverflow.com/questions/3645609/reload-the-page-on-hitting-back-button -->
            <input id="alwaysFetch" type="hidden" />
            <script>
                setTimeout(function () {
                    var el = document.getElementById('alwaysFetch');
                    el.value = el.value ? location.reload() : true;
                }, 0);
            </script>

        </div>


    </body>

    <!-- JQuery -->
    <script
        src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
        crossorigin="anonymous">
    </script>

    <!-- Custom JS -->
    <script>
        $(document).ready(function() {

            // SUBMIT BUTTON EVENT LISTENER
            $("#submit").click(function(){
                 var r = $('#rating').val();
                 var c = $('#comments').val();
                 //alert(rating + " : " + comments);
                 
                 $.ajax({
                    type: "POST",
                    url: "ajax.php",
                    data : {
                        rating : r,
                        comments : c
                    },
                    dataType: "json",
                    success: function(result){
                        if (result == "success")
                            location.href = 'success.html';
                        else
                            alert("Days since last rating: " + result + ". You can only submit a review once every 30 days.");
                    },
                    error: function(errMsg) {
                        alert("Error occurred: " + JSON.stringify(errMsg));
                    }
                });
                
            });

            //rating_buttons = [];
            // for each rating button (id's 1-5)
            $("#button_group .btn-check").each(function () {
                // build numbered array of buttons 1-5
                //rating_buttons[this.id] = this;;

                // CHECK/UNCHECK LISTENERS
                $(this).change( function() {

                    if ($(this).is(':checked'))
                    {
                        // clear all checks
                        for (i=1; i<6; i++)
                            $("#" + i).prop("checked", false);
                        // check all that are less than *or equal* to this.id
                        for (i=this.id; i>0; i--)
                            $("#" + i).prop("checked", true);
                        // update rating = this.id
                        rating = this.id;
                        $('#rating_display').text(rating + '/5');
                        $('#rating').val(rating);
                    }
                    else
                    {
                        // clear all checks
                        for (i=1; i<6; i++)
                            $("#" + i).prop("checked", false);
                        // check all that are *less than* this.id
                        for (i=1; i<this.id; i++)
                            $("#" + i).prop("checked", true);
                        // update rating = this.id - 1
                        rating = (parseInt(this.id)) - 1;
                        $('#rating_display').text(rating + '/5');
                        $('#rating').val(rating);
                    }
                });

            });

        });


    </script>
</html>