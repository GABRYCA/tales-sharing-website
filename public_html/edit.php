<?php
include_once (dirname(__FILE__) . "/../private/objects/Gallery.php");
include_once (dirname(__FILE__) . "/../private/objects/User.php");
include_once (dirname(__FILE__) . "/../private/objects/Content.php");
include_once(dirname(__FILE__) . '/common/utility.php');
session_start();

// If there's already an active session, send user to home.php.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}

// Check if method is get, if not stop.
if ($_SERVER["REQUEST_METHOD"] != "GET") {
    exit("Invalid request method.");
}

if (empty($_GET["id"])) {
    exit("Content id is empty");
}

// Get content id
$contentId = validate_input($_GET["id"]);

// Check if contentId is a number
if (!is_numeric($contentId)) {
    exit("Content id is not a number");
}

// Check if content exists
$content = new Content();
$content->setContentId($contentId);
if (!$content->loadContent()) {
    exit("Content does not exist");
}

// Get user from session
$user = new User();
$user->setUsername($_SESSION["username"]);
$user->loadUser();

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once (dirname(__FILE__) . "/common/common-head.php");
    ?>
    <title>Edit Content - <?= $content->getTitle() ?></title>
    <style>
        .tag-input {
            /* border: 1px solid black; */
            padding: 5px;
            font-family: Arial, sans-serif;
        }

        .tag {
            display: inline-block;
            background-color: rgba(173, 216, 230, 0.4);
            border-radius: 5px;
            padding: 2px 10px;
            margin: 2px;
            cursor: pointer;
            transition: 0.15s ease-out;
        }

        .tag:hover {
            background-color: rgba(173, 216, 230, 0.8);
        }

        /* Hide the suggestion list by default */
        .suggestion-list {
            display: none;
        }

        /* Show the suggestion list as a dropdown list */
        .suggestion-list {
            list-style: none;
            margin: 0;
            padding: 0;
            border: 1px solid #343a40;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 5px; /* Aggiungi il bordo arrotondato alla lista */
        }

        /* Show the list items with a dark background and a pointer cursor */
        .suggestion-list li {
            background-color: rgb(14, 17, 22);
            color: white;
            padding: 10px;
            cursor: pointer;
        }

        /* Change the background color of the list items when the mouse hovers over them */
        .suggestion-list li:hover {
            background: linear-gradient(90deg, rgb(0, 0, 139) 0%, rgb(139, 0, 139) 100%);
        }

        /* Style the caret on open */
        .suggestion-list li:first-child {
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        .suggestion-list li:last-child {
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
        }

        #loader {
            width: 50px;
            height: 50px;
            border: 5px solid transparent;
            border-image: linear-gradient(90deg, rgba(0,97,255,1) 0%, rgba(255,15,123,1) 100%);
            border-image-slice: 1;
            border-radius: 50%;
            margin: auto;
            animation: spin 1s linear infinite;
        }

        #upload {
            background: linear-gradient(90deg, rgba(0,97,255,1) 0%, rgba(255,15,123,1) 100%) !important;
        }

        #upload:hover {
            background: linear-gradient(90deg, rgba(0,97,255,0.7) 0%, rgba(255,15,123,0.7) 100%) !important;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-dark font-monospace text-light">
<div class="container-fluid">
    <div class="row text-light justify-content-around mt-3 p-3">
        <!-- Button to go back to home.php -->
        <div class="col-6">
            <a href="home.php" class="btn btn-outline-light p-3 w-100"><- Home |</a>
        </div>
        <!-- Button to go to profile.php -->
        <div class="col-6">
            <a href="profile.php" class="btn btn-outline-light p-3 w-100">| Profile -></a>
        </div>
    </div>
    <hr>
    <?php
    if (!$user->canUpload()){
        echo '<h1 class="text-center text-danger text-decoration-underline">You are not allowed to edit or upload content!</h1>';
        echo '<h3 class="text-center text-danger">Please contact an administrator if you think this is a mistake. <a class="link-danger" href="mailto:anonymousgca@anonymousgca.eu">anonymousgca@anonymousgca.eu</a></h3>';
    }
    ?>
    <div class="row justify-content-center">
        <div class="col mx-0 mt-2">
            <h1 class="text-center">Edit Content</h1>
        </div>
    </div>

    <!-- A row with the input description (title, description, isPrivate and isAI checkmarks) hidden until the user selects an image -->
    <div class="row mb-3 justify-content-center" id="inputDescription">

        <div class="col">

            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <hr>
                </div>
            </div>

            <!-- Image from server -->
            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <img src="<?= $content->getUrlImage() ?>" class="img-fluid rounded mx-auto d-block" alt="Image">
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <hr>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <!-- Title input -->
                    <p class="text-start fs-5 mb-0 mx-1">Title:</p>
                    <div class="form-floating mb-3 mt-0">
                        <input type="text" class="form-control" id="name" name="name" placeholder="Title" value="<?= $content->getTitle() ?>">
                        <label for="name">Title</label>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <!-- Description input -->
                    <textarea id="DescriptionEditor"></textarea>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <hr>
                </div>
            </div>

            <!-- Row with tags input, separated by a "," each tag -->
            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <!-- Use a form-group to wrap the tag input -->
                    <div class="form-group">
                        <label for="tag-input" class="mb-1 mx-1">Enter tags separated by commas:</label>
                        <br class="w-100">
                        <!-- Use a contenteditable div as the tag input -->
                        <div id="tag-input" class="tag-input form-control mt-1" contenteditable="true"></div>
                        <!-- Use a hidden input to store the tags -->
                        <input id="tag-input-hidden" type="hidden" name="tags" value="" />
                        <!-- Use a ul to display suggestions -->
                        <ul id="suggestion-list" class="suggestion-list"></ul>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <hr>
                </div>
            </div>

            <div class="row justify-content-center">

                <p class="fs-4 text-center">Options:</p>
                <!-- Checkmarks isPrivate and isAI -->
                <div class="col-6 col-lg-3 text-center">
                    <input class="form-check-input" type="checkbox" role="switch" id="isPrivate" name="isPrivate">
                    <label class="form-check-label" for="isPrivate">Private</label>
                </div>
                <div class="col-6 col-lg-3 text-center">
                    <input class="form-check-input" type="checkbox" role="switch" id="isAI" name="isAI">
                    <label class="form-check-label" for="isAI">AI - Generated</label>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <hr>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-6 text-center d-flex align-items-center">
                    <div id="loader"></div>
                </div>
            </div>

            <div class="row mb-3 mt-5 justify-content-center">
                <div class="col-5 col-lg-2">
                    <!-- Cancel button -->
                    <button class="btn btn-secondary w-100 pt-2 pb-2 border border-0 fs-4" id="cancel">Cancel</button>
                </div>
                <div class="col-5 col-lg-2 ps-0">
                    <!-- Submit button -->
                    <button class="btn btn-success w-100 pt-2 pb-2 border border-0 fs-4" id="upload">Save</button>
                </div>
                <div class="col-2 col-lg-2 ps-0">
                    <!-- Trash button fontawesome to delete content, on click will open modal for confirmation -->
                    <button class="btn btn-danger w-100 pt-2 pb-2 border border-0 fs-4" id="delete" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        </div>

    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this content?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-deletion">Confirm</button>
                </div>
            </div>
        </div>
    </div>

</div>
<?php
include_once (dirname(__FILE__) . '/common/common-footer.php');
include_once (dirname(__FILE__) . "/common/common-body.php");
?>
<script>

    var content = `<?= addslashes(html_entity_decode ($content->getDescription())) ?>`;

    tinymce.init({
        selector: 'textarea#DescriptionEditor',
        skin: 'oxide-dark',
        content_css: 'dark',
        block_unsupported_drop: true,
        branding: false,
        convert_fonts_to_spans: false,
        setup: function (editor) {
            editor.on('init', function (e) {
                editor.setContent(content);
            });
        },
        images_upload_handler: function () {}
    });

    $(function (){
        // Remove after 1 second from the loading of the page, the whole div with class tox-promotion
        setTimeout(function (){
            $('.tox-promotion').remove();
        }, 1000);

        // Given the tags from $content->getTags() I set them in the hidden input and create the tag elements
        <?php
        foreach ($content->getTagsOfContent() as $tag) {
            echo "addTag('" . $tag->getName() . "');";
        }
        ?>

        // Set the isPrivate and isAI checkmarks
        <?php
        if ($content->getIsPrivate()) {
            echo "$('#isPrivate').prop('checked', true);";
        }

        if ($content->getIsAI()) {
            echo "$('#isAI').prop('checked', true);";
        }
        ?>
    });

    $(function(){

        // Create a variable to store the loader element
        var $loader = $('#loader');

        // Hide the loader element initially
        $loader.hide();

        // Bind submit event to the form element
        $('#upload').on('click', function(e) {
            tinymce.triggerSave();
            // Prevent the default form submission action
            e.preventDefault();

            var name = $('#name').val();
            var description = tinyMCE.activeEditor.getContent();
            var isPrivate = $('#isPrivate').is(':checked') ? 1 : 0;
            var isAI = $('#isAI').is(':checked') ? 1 : 0;
            var tags = getTags();

            // Check if name is empty or null
            if (name === "" || name === null) {
                // Show a toast
                $.toast({
                    heading: 'Error',
                    text: 'Please enter a name for the image.',
                    showHideTransition: 'slide',
                    icon: 'error',
                    position: 'top-right'
                });
                return;
            }

            // Check if description is empty or null
            if (description === "" || description === null) {
                // Show a toast
                $.toast({
                    heading: 'Error',
                    text: 'Please enter a description for the image.',
                    showHideTransition: 'slide',
                    icon: 'error',
                    position: 'top-right'
                });
                return;
            }

            // The gallery is optional, so we don't need to check it
            // Same for isPrivate and isAI
            var finalFile = new FormData();
            finalFile.append('name', name);
            finalFile.append('description', description);
            finalFile.append('isPrivate', isPrivate.toString());
            finalFile.append('isAI', isAI.toString());
            finalFile.append('contentId', <?= $contentId ?>); // Add the id of the image to edit (passed in the URL
            finalFile.append("action", "edit");

            // Add tags to the FormData using a for loop to make an array
            for (var i = 0; i < tags.length; i++) {
                finalFile.append('tags[]', tags[i]);
            }

            // For debug, print in console finalFile and tags.
            //console.log(...finalFile);
            //console.log(tags);
            //return;

            // Send it to the server using jQuery AJAX along with other form data
            $.ajax({
                url: 'actions/upload.php', // The URL of your PHP script that handles the upload
                type: 'POST', // The HTTP method to use
                processData: false,
                contentType: false,
                data: finalFile, // The file data and other form data as key-value pairs
                beforeSend: function() {
                    // Show the loader element before sending the request
                    $loader.show();
                },
                complete: function() {
                    // Hide the loader element after completing the request
                    $loader.hide();
                },
                success: function(response) {
                    // Toast with output from upload.php
                    $.toast({
                        text: response + ' \n- You will be redirect to your content.',
                        icon: 'info',
                        position: 'top-right',
                        hideAfter: 3000,
                    });

                    // After 3 seconds, reload the page
                    setTimeout(function() {
                        // Send user to share.php?id=lastID
                        window.location.href = "share.php?id=" + <?= $contentId ?>;
                    }, 3000);
                },
                error: function(error) {
                    // Toast with error from upload.php
                    $.toast({
                        text: error,
                        icon: 'error',
                        position: 'top-right'
                    });
                }
            });
        });

        // If I click the cancel button, go back to the share page
        $('#cancel').on('click', function(e) {
            e.preventDefault();
            window.location.href = "share.php?id=" + <?= $contentId ?>;
        });

        // If I click confirm-deletion button, delete the image and go back to home.php
        $('#confirm-deletion').on('click', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'actions/upload.php', // The URL of your PHP script that handles the upload
                type: 'POST', // The HTTP method to use
                data: {
                    contentId: <?= $contentId ?>,
                    action: "delete"
                },
                success: function(response) {
                    // Toast with output from upload.php
                    $.toast({
                        text: response + ' \n- You are about to be redirected to the homepage.',
                        icon: 'info',
                        position: 'top-right',
                        hideAfter: 3000,
                    });

                    // Close modal.
                    $('#deleteModal').modal('hide');

                    // After 3 seconds, go home.php
                    setTimeout(function() {
                        // Send user to home.php
                        window.location.href = "home.php";
                    }, 3000);
                },
                error: function(error) {
                    // Toast with error from upload.php
                    $.toast({
                        text: error,
                        icon: 'error',
                        position: 'top-right'
                    });
                }
            });
        });

    });

    // Get the tag input element by id
    var tagInput = $("#tag-input");

    // Add an input event listener
    tagInput.on("input", function (e) {
        // Get the input value
        var value = $(this).text();

        // Check if the last character is a comma
        if (value.slice(-1) == ",") {
            // Remove the comma and any extra spaces
            value = value.slice(0, -1).trim();

            // Add the tag to the hidden input and create the tag element
            addTag(value);

            // Clear the input
            $(this).empty();
        } else {
            if (value !== "") {
                // Send an ajax request to get suggestions
                $.ajax({
                    url: "actions/tagService.php", // Your PHP service that returns tags in JSON format
                    type: "GET",
                    data: { q: value }, // The query to send to the service
                    dataType: "json",
                    success: function (data) {
                        // If the request succeeds, show the results in the dropdown list
                        showSuggestions(data);
                    },
                    error: function (error) {
                        // If the request fails, show an error message
                        console.log(error);
                    },
                });
            } else {
                // If the value is empty, hide the suggestions list
                hideSuggestions();
            }
        }
    });

    function showSuggestions(data) {
        // Get the dropdown list element by id
        var list = $("#suggestion-list");
        // Empty the list content
        list.empty();
        // Get the tags as an array
        var tags = getTags();
        // Filter the data to remove the tags that already exist in the input
        data = data.filter(function (item) {
            return tags.indexOf(item.name) == -1;
        });
        // For each item of the data received, create a li element with the tag name
        $.each(data, function (index, item) {
            var li = $("<li></li>").text(item.name);
            // Add a click event on the li element to add the selected tag to the input
            li.click(function () {
                addTag(item.name);
                // Clear the input content
                $("#tag-input").empty();
                // Hide the suggestions list
                hideSuggestions();
            });
            // Append the li element to the list
            list.append(li);
        });
        // Show the dropdown list
        list.show();
    }

    // Function to hide suggestions in the dropdown list
    function hideSuggestions() {
        // Get the dropdown list element by id
        var list = $("#suggestion-list");
        // Hide the dropdown list
        list.hide();
    }

    // Function to add a tag to the hidden input
    function addTag(tag) {
        // Get the hidden input element by id
        var input = $("#tag-input-hidden");
        // Get the current input value
        var value = input.val();
        // Get the tags as an array
        var tags = getTags();
        // Check if the tag already exists in the array
        if (tags.indexOf(tag) == -1) {
            // If not, add the tag to the input value
            // If the value is not empty, add a comma before the tag
            if (value != "") {
                value += ",";
            }
            value += tag;
            // Set the new input value
            input.val(value);
            // Create a new tag element
            var tagElement = $("<span class='tag'></span>");
            tagElement.text(tag);
            // If clicks the button, remove the tag (but only the one clicked)
            tagElement.on("click", function () {
                removeTag(tag);
                $(this).remove();
            });
            // Insert the tag before the input
            $("#tag-input").before(tagElement);
        }
    }

    // Function to remove a tag from the hidden input
    function removeTag(tag) {
        // Get the hidden input element by id
        var input = $("#tag-input-hidden");
        // Get the current input value
        var value = input.val();
        // Check if the value exists
        if (value) {
            // Split the value by comma
            var tags = value.split(",");
            // Find the index of the tag to remove
            var index = tags.indexOf(tag);
            // If the index is found, remove the tag from the array
            if (index > -1) {
                tags.splice(index, 1);
            }
            // Join the array by comma
            value = tags.join(",");
            // Set the new input value
            input.val(value);
        }
    }

    // Function to get the tags as an array
    function getTags() {
        // Get the hidden input element by id
        var input = $("#tag-input-hidden");
        // Get the current input value
        var value = input.val();
        // Split the value by comma and return the array
        return value.split(",");
    }

</script>
</body>
</html>
