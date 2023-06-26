<?php
include_once (dirname(__FILE__) . "/../private/objects/Gallery.php");
include_once (dirname(__FILE__) . "/../private/objects/User.php");
session_start();

// If there's already an active session, send user to home.php.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}

// Get user from session
$user = $_SESSION["user"];

// Get Galleries of user and also cast
$galleries = $user->getGalleries();

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once (dirname(__FILE__) . "/common/common-head.php");
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css" integrity="sha512-wJgJNTBBkLit7ymC6vvzM1EcSWeM9mmOu+1USHaRBbHkm6W9EgM0HY27+UtUaprntaYQJF75rc8gjxllKs5OIQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="data/util/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js" integrity="sha512-zlWWyZq71UMApAjih4WkaRpikgY9Bz1oXIW5G0fED4vk14JjGlQ1UmkGM392jEULP8jbNMiwLWdM8Z87Hu88Fw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <title>Upload Content</title>
    <style>
        #drop-area {}

        #drop-area:hover {
            border-color: purple !important;
            cursor: pointer;
        }

        #upload {
            background: rgb(0,97,255) !important;
            background: linear-gradient(90deg, rgba(0,97,255,1) 0%, rgba(255,15,123,1) 100%) !important;
            transition: 0.3s ease-out !important;
        }

        #upload:hover {
            background: #d2186e !important;
            font-weight: bolder !important;
            color: rgb(42, 42, 42) !important;
        }

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

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>

    <script>

        // Declare a global variable to store the file data
        var fileData = null;

        $(function() {

            // Create a variable to store the loader element
            var $loader = $('#loader');

            // Hide the loader element initially
            $loader.hide();

            $('#createGallery').on('click', function(){
                // Get from input fron newGallery (the name of the new gallery)
                var newGallery = $('#newGallery').val();
                // If the input is empty, show a toast
                if (newGallery === "") {
                    $.toast({
                        heading: 'Error',
                        text: 'Please enter a name for the new gallery.',
                        showHideTransition: 'slide',
                        icon: 'error',
                        position: 'top-right'
                    });
                } else {
                    // If the input is not empty, create a gallery
                    createGallery(newGallery);
                }
            });

            // Bind dragover and dragenter events to prevent default actions
            $('#drop-area').on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });

            // Bind drop event and access the dropped file with e.originalEvent.dataTransfer.files
            $('#drop-area').on('drop', function(e) {
                if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Get the first dropped file
                    var file = e.originalEvent.dataTransfer.files[0];
                    // Create a FileReader object
                    var reader = new FileReader();
                    // Read the file data as a base64 encoded string
                    reader.readAsDataURL(file);
                    // When the file is loaded, store it in the global variable and show a preview
                    reader.onload = function(e) {
                        // Get the file data
                        fileData = e.target.result;
                        // Display a message to indicate that the file is ready to upload
                        loadedImage();
                    };
                }
            });

            $('#fileElem').on('change', function(e) {
                if (e.target.files && e.target.files.length) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Get the first dropped file
                    var file = e.target.files[0];
                    // Create a FileReader object
                    var reader = new FileReader();
                    // Read the file data as a base64 encoded string
                    reader.readAsDataURL(file);
                    // When the file is loaded, store it in the global variable and show a preview
                    reader.onload = function(e) {
                        // Get the file data
                        fileData = e.target.result;
                        // Display a message to indicate that the file is ready to upload
                        loadedImage();
                    };
                }
            });

            // Bind submit event to the form element
            $('#upload').on('click', function(e) {
                tinymce.triggerSave();
                // Prevent the default form submission action
                e.preventDefault();
                // Check if the file data is not null
                if (fileData) {

                    var name = $('#name').val();
                    var description = tinyMCE.activeEditor.getContent();
                    var gallery = $('#selectGallery').val();
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
                    finalFile.append('file', fileData);
                    finalFile.append('name', name);
                    finalFile.append('description', description);
                    finalFile.append('gallery', gallery);
                    finalFile.append('isPrivate', isPrivate.toString());
                    finalFile.append('isAI', isAI.toString());

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
                                text: response + ' \nPlease wait for the page reload before uploading again.',
                                icon: 'info',
                                position: 'top-right',
                                hideAfter: 3000,

                            });

                            // Reset inputs and fileData
                            $('#name').val('');
                            tinyMCE.activeEditor.setContent('');
                            $('#selectGallery').val('');
                            $('#isPrivate').prop('checked', false);
                            $('#isAI').prop('checked', false);
                            fileData = null;

                            // After 10 seconds, reload the page
                            setTimeout(function() {
                                location.reload();
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
                } else {
                    // Display a message to indicate that no file was dropped
                    $('#drop-area').text('Please drop a file first');
                    // Remove p-3 and change it to p-5
                    $('#drop-area').removeClass('p-3');
                    $('#drop-area').addClass('p-5');
                }
            });
        });

        function createGallery(galleryName) {
            // Using ajax, I send to galleryManager with the action type of create and the gallery name
            $.ajax({
                url: 'actions/galleryManager.php',
                type: 'POST',
                data: {
                    action: 'create',
                    galleryName: galleryName,
                },
                success: function (data) {

                    // Send toast with data
                    $.toast({
                        text: data,
                        icon: 'success',
                        position: 'top-center',
                        showHideTransition: 'slide',
                        showDuration: 500,
                        hideDuration: 500,
                        loader: false,
                        allowToastClose: true,
                        hideAfter: 3000,
                        stack: false,
                        textAlign: 'center',
                        positionLeft: false,
                        positionRight: true,
                        bgColor: '#6600e1',
                        textColor: '#fff',
                    });

                    reloadGalleries();
                },
                error: function (data) {
                    // Send toast with data
                    $.toast({
                        text: data,
                        icon: 'error',
                        position: 'top-center',
                        showHideTransition: 'slide',
                        showDuration: 500,
                        hideDuration: 500,
                        loader: false,
                        allowToastClose: true,
                        hideAfter: 3000,
                        stack: false,
                        textAlign: 'center',
                        positionLeft: false,
                        positionRight: true,
                        bgColor: '#6600e1',
                        textColor: '#fff',
                    });
                }
            })
        }

        function reloadGalleries(){
            // Clear select with id selectGallery
            $('#selectGallery').empty();
            // Clear also input with id newGallery
            $('#newGallery').val('');
            // Get galleries from user
            $.ajax({
                url: 'actions/galleryManager.php',
                type: 'POST',
                data: {
                    action: 'list'
                },
                success: function (data) {
                    // Parse data
                    var galleries = JSON.parse(data);
                    // For each gallery, append an option to the select
                    galleries.forEach(function (gallery) {
                        $('#selectGallery').append('<option value="' + gallery.galleryId + '">' + gallery.name + '</option>');
                    });
                }
            });
        }

        function loadedImage() {
            $('#drop-area').text('File ready to upload');
            // Remove p-5 and change it to p-3
            $('#drop-area').removeClass('p-5');
            $('#drop-area').addClass('p-3');
            // Create an image element with the file data as the source
            var image = $('<img>').attr('src', fileData);
            // Add classes to image element
            image.addClass('img-fluid img-thumbnail rounded-5 mt-2');
            // Append the image element to the drop area
            $('#drop-area').append(image);
            // Show the input description
            $('#inputDescription').removeClass('d-none');
        }

    </script>
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
    <div class="row justify-content-center">
        <div class="col mx-0 mt-2">
            <h1 class="text-center">Upload Content</h1>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col">
            <!-- Drag and Drop upload area image using jQuery -->
            <div class="container-fluid mb-3">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-6">
                        <div id="drop-area" class="bg-secondary bg-opacity-25 border border-3 border-light border-opacity-25 rounded-4 text-light text-center p-5">
                            <h1>Drag and Drop Image Here</h1>
                            <p>or</p>
                            <input class="form-control" type="file" id="fileElem" multiple accept="image/*">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- A row with the input description (title, description, isPrivate and isAI checkmarks) hidden until the user selects an image -->
    <div class="row mb-3 justify-content-center d-none" id="inputDescription">
        <div class="col">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <!-- Title input -->
                    <p class="text-start fs-5 mb-0 mx-1">Title:</p>
                    <div class="form-floating mb-3 mt-0">
                        <input type="text" class="form-control" id="name" name="name" placeholder="Title">
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

            <!-- Row with tags input, separated by a "," each tag -->
            <div class="row justify-content-center mt-2">
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

                <div class="col-12 col-lg-6">
                    <div class="row justify-content-center">
                        <div class="col-6">
                            <!-- List of galleries selectable (using the session user and Gallery.php I get the list of galleries) -->
                            <p class="fs-4 text-center">Gallery: (Optional)</p>

                            <select class="form-select" aria-label="Select Galleries" id="selectGallery" name="galleryName">
                                <option selected>Select gallery</option>
                                <?php
                                foreach ($galleries as $gallery) {
                                    echo "<option value='" . $gallery->getGalleryId() . "'>" . $gallery->getName() . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <!-- Create a new gallery  -->
                        <div class="col-6">
                            <p class="fs-4 text-center">Create new gallery:</p>
                            <div class="row justify-content-center">
                                <div class="col-9 p-0">
                                    <input type="text" class="form-control" id="newGallery" placeholder="New Gallery" name="newGallery">
                                </div>
                                <div class="col-3 px-1">
                                    <button class="btn btn-primary w-100 border border-0" id="createGallery">+</button>
                                </div>
                            </div>
                        </div>
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
                <div class="col-12 col-lg-6">
                    <!-- Submit button -->
                    <button class="btn btn-primary w-100 pt-2 pb-2 border border-0 fs-4" id="upload">Submit</button>
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
    tinymce.init({
        selector: 'textarea#DescriptionEditor',
        skin: 'oxide-dark',
        content_css: 'dark',
        block_unsupported_drop: true,
        branding: false,
        images_upload_handler: function () {}
    });

    $(function (){
        // Remove after 1 second from the loading of the page, the whole div with class tox-promotion
        setTimeout(function (){
            $('.tox-promotion').remove();
        }, 1000);
    })

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
