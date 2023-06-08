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
    </style>

    <script>

        // Declare a global variable to store the file data
        var fileData = null;

        $(function() {

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
                // Prevent the default form submission action
                e.preventDefault();
                // Check if the file data is not null
                if (fileData) {

                    var name = $('#name').val();
                    var description = tinyMCE.activeEditor.getContent();
                    var gallery = $('#selectGallery').val();
                    var isPrivate = $('#isPrivate').is(':checked') ? 1 : 0;
                    var isAI = $('#isAI').is(':checked') ? 1 : 0;

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

                    // Send it to the server using jQuery AJAX along with other form data
                    $.ajax({
                        url: 'actions/upload.php', // The URL of your PHP script that handles the upload
                        type: 'POST', // The HTTP method to use
                        processData: false,
                        contentType: false,
                        data: finalFile, // The file data and other form data as key-value pairs
                        success: function(response) {
                            // Toast with output from upload.php
                            $.toast({
                                text: response + ' \nPlease wait for the page reload before uploading again.',
                                icon: 'info',
                                position: 'top-right',
                                hideAfter: 10000,

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
                            }, 10000);
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
            <a href="home.php" class="btn btn-outline-light p-3 w-100">Home</a>
        </div>
        <!-- Button to go to profile.php -->
        <div class="col-6">
            <a href="profile.php" class="btn btn-outline-light p-3 w-100">Profile</a>
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

</script>
</body>
</html>
