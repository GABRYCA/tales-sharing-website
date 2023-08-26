<?php
include_once(dirname(__FILE__) . "/../connection.php");
include_once(dirname(__FILE__) . "/../objects/VariablesConfig.php");
include_once(dirname(__FILE__) . "/../objects/User.php");

class UniqueImage
{
    private $errorStatus = null;
    private $name = null;
    private $path = null;

    /**
     * Initialize Object.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Function to create a unique image
     * Please set $name before calling this function
     * @return bool
     */
    public function createUniqueProfileImage()
    {
        // Load the user
        $user = new User();
        $user->setUsername($this->name);

        if (!$user->loadUser()){
            // Set error status
            $this->errorStatus = $user->getErrorStatus();
            // Error message
            return false;
        }

        $width = 150;
        $height = 150;

        // Create a blank image with a width of 150 pixels and a height of 150 pixels
        $image = imagecreatetruecolor($width, $height);

        // Use the create_gradient_image function to create a gradient image
        $gradient = $this->create_gradient_image($width, $height); // You can change the colors as you wish

        // Copy the gradient image to the blank image
        imagecopy($image, $gradient, 0, 0, 0, 0, $width, $height);

        // Set the font path and size
        $font = dirname(__FILE__) . "/../fonts/arial/arial.ttf"; // Replace this with the path of your font file
        $font_size = 50;

        // Get the user's name or initials from the database
        $user_name = $this->name; // Replace this with your code to get the user's name from the database
        $user_initials = substr($user_name, 0, 1) . substr(strrchr($user_name, ' '), 1); // Extract the first letter of the first name and the last name

        // Calculate the position of the text on the image
        $text_box = imagettfbbox($font_size, 0, $font, $user_initials);
        $text_width = $text_box[2] - $text_box[0];
        $text_height = $text_box[7] - $text_box[1];
        $text_x = ($width - $text_width) / 2;
        $text_y = ($height - $text_height) / 2;

        // Create some colors for the text and its effects
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 0, 0);

        // Write the text on the image with a red color and a cool font
        imagettftext($image, $font_size, 0, $text_x, $text_y, $red, $font, $user_initials);

        // Add a white outline to the text
        imagettftext($image, $font_size, 0, $text_x-1, $text_y-1, $white, $font, $user_initials);
        imagettftext($image, $font_size, 0, $text_x+1, $text_y-1, $white, $font, $user_initials);
        imagettftext($image, $font_size, 0, $text_x-1, $text_y+1, $white, $font, $user_initials);
        imagettftext($image, $font_size, 0, $text_x+1, $text_y+1, $white, $font, $user_initials);

        // Add a black shadow to the text
        //imagettftext($image, $font_size+2 , -5 , ($text_x+3), ($text_y+3), ($black), ($font), ($user_initials));

        // Convert image to string
        ob_start();
        imagepng($image);
        $image = ob_get_contents();
        ob_end_clean();


        // Save image to the server
        $this->setPath($this->save_image($image, $this->name, $this->name));

        // Save new URL profile picture
        if (!$user->changeProfilePicture($this->path)) {
            // Error message
            $this->setErrorStatus($user->getErrorStatus());
            return false;
        }

        // Return true
        return true;
    }

    /**
     * Function to create a unique image
     *
     * @param string $image base64 decoded image
     * @param string $user_id
     * @param string $title
     *
     * @return string
     */
    private function save_image($image, $user_id, $title): string
    {
        // Make sure that title doesn't break paths.
        $title = preg_replace("/([^\w\s\d\-_~,;[\]\(\).])/", "", $title);

        // Create a new image from the decoded image data.
        $image = imagecreatefromstring($image);
        // Get the image width.
        $image_width = imagesx($image);
        // Get the image height.
        $image_height = imagesy($image);
        // Create a new image with the same width and height, keeping transparent background if there's.
        $new_image = imagecreatetruecolor($image_width, $image_height);
        // Set the flag to save full alpha channel information.
        imagesavealpha($new_image, true);
        // Copy the image to the new image.
        imagecopy($new_image, $image, 0, 0, 0, 0, $image_width, $image_height);
        // The uniqueid
        $uniqueid = uniqid();

        // Create directories if there aren't already
        if (!file_exists(dirname(__FILE__) . "/../../public_html/data/profile/" . $user_id . "/gallery/images/")) {
            mkdir(dirname(__FILE__) . "/../../public_html/data/profile/" . $user_id . "/gallery/images/", 0777, true);
        }
        // Save the image to the server as a .webp
        imagewebp($new_image, dirname(__FILE__) . "/../../public_html/data/profile/" . $user_id . "/gallery/images/" . $title . "-" . $uniqueid . ".webp", 80);

        // Return the image path
        return VariablesConfig::$domain . "data/profile/" . $user_id . "/gallery/images/" . $title . "-" . $uniqueid . ".webp";
    }

    // Define a function that creates a gradient image
    private function create_gradient_image($width, $height) {
        // Create an image resource with the given width and height
        $image = imagecreatetruecolor($width, $height);

        // Convert the hex colors to RGB values
        $start_color = $this->hex2rgb(rand(0, 16777215));
        $end_color = $this->hex2rgb(rand(0, 16777215));

        // Calculate the color steps
        $red_step = ($start_color[0] - $end_color[0]) / $height;
        $green_step = ($start_color[1] - $end_color[1]) / $height;
        $blue_step = ($start_color[2] - $end_color[2]) / $height;

        // Loop through each row of the image
        for ($y = 0; $y < $height; $y++) {
            // Calculate the current color
            $red = $start_color[0] - ($red_step * $y);
            $green = $start_color[1] - ($green_step * $y);
            $blue = $start_color[2] - ($blue_step * $y);

            // Allocate the color
            $color = imagecolorallocate($image, $red, $green, $blue);

            // Fill the row with the color
            imagefilledrectangle($image, 0, $y, $width, $y + 1, $color);
        }

        // Return the image resource
        return $image;
    }

    // Define a function that converts a hex color to RGB values
    private function hex2rgb($hex) {
        // Remove the hash symbol if present
        if (substr($hex, 0, 1) == '#') {
            $hex = substr($hex, 1);
        }

        // Convert the hex values to decimal values
        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        // Return an array of RGB values
        return array($red, $green, $blue);
    }


    /**
     * Set error status.
     * @param string $errorStatus
     */
    private function setErrorStatus(string $errorStatus)
    {
        $this->errorStatus = $errorStatus;
    }

    /**
     * Get error status.
     * @return string
     */
    public function getErrorStatus(): string
    {
        return $this->errorStatus;
    }

    /**
     * Get name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name.
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get path.
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set path.
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }
}