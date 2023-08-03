<?php
include_once (dirname(__FILE__) . "/../connection.php");
include_once (dirname(__FILE__) . "/../objects/Content.php");

class Gallery implements JsonSerializable
{
    private $galleryId = null;
    private $ownerId = null;
    private $name = null;
    private $hideGallery = false;
    private $urlCoverGallery = null;
    private $errorStatus = null;

    /**
     * Function to load gallery info from database following $galleryId.
     * @return bool
     */
    public function loadGalleryInfoByGalleryId() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM GalleryGroup WHERE galleryId = ? ORDER BY galleryId DESC";

        if ($data = $conn->execute_query($sql, [$this->galleryId])){

            if ($data->num_rows == 0) {
                $this->setErrorStatus("Gallery not found");
                return false;
            }

            $row = $data->fetch_assoc();
            $this->ownerId = $row["ownerId"];
            $this->name = $row["name"];
            $this->hideGallery = (bool) $row["hideGallery"];
            $this->urlCoverGallery = $row["urlCoverGallery"];
        } else {
            $this->setErrorStatus("Error while loading gallery");
            return false;
        }

        return true;
    }

    /**
     * Function to load gallery info from database following $ownerId and $galleryName.
     * @return bool
     */
    public function loadGalleryInfoByOwnerId() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM GalleryGroup WHERE ownerId = ? AND name = ? ORDER BY galleryId DESC";

        if ($data = $conn->execute_query($sql, [$this->ownerId, $this->name])){
            $row = $data->fetch_assoc();
            $this->galleryId = $row["galleryId"];
            $this->name = $row["name"];
            $this->hideGallery = $row["hideGallery"];
            $this->urlCoverGallery = $row["urlCoverGallery"];
        } else {
            $this->setErrorStatus("Error while loading gallery");
            return false;
        }

        return true;
    }

    /**
     * Function that returns the full list of galleries using $ownerId.
     * @return Gallery[]
     */
    public function getGalleriesByOwnerId() : array
    {
        $conn = connection();

        $sql = "SELECT * FROM GalleryGroup WHERE ownerId = ?";

        $galleries = array();
        if ($data = $conn->execute_query($sql, [$this->ownerId])){
            foreach ($data as $row) {
                $gallery = new Gallery();
                $gallery->setGalleryId($row["galleryId"]);
                $gallery->setOwnerId($row["ownerId"]);
                $gallery->setName($row["name"]);
                $gallery->setHideGallery($row["hideGallery"]);
                $gallery->setUrlCoverGallery($row["urlCoverGallery"]);
                $galleries[] = $gallery;
            }
        }

        // If the array is empty set error status
        if (empty($galleries)) {
            $this->setErrorStatus("There are no galleries to show");
        }

        return $galleries;
    }

    /**
     * Function that returns the list of galleries using $ownerId that aren't hidden.
     * @return Gallery[]
     */
    public function getGalleriesByOwnerIdNotHidden() : array
    {
        $conn = connection();

        $sql = "SELECT * FROM GalleryGroup WHERE ownerId = ? AND hideGallery = 0";

        $galleries = array();
        if ($data = $conn->execute_query($sql, [$this->ownerId])){
            foreach ($data as $row) {
                $gallery = new Gallery();
                $gallery->setGalleryId($row["galleryId"]);
                $gallery->setOwnerId($row["ownerId"]);
                $gallery->setName($row["name"]);
                $gallery->setHideGallery($row["hideGallery"]);
                $gallery->setUrlCoverGallery($row["urlCoverGallery"]);
                $galleries[] = $gallery;
            }
        }

        // If the array is empty set error status
        if (empty($galleries)) {
            $this->setErrorStatus("There are no galleries to show");
        }

        return $galleries;
    }

    /**
     * Function to create a new gallery.
     * @return bool
     */
    public function createGallery() : bool
    {
        $conn = connection();

        $sql = "INSERT INTO GalleryGroup (ownerId, name, hideGallery) VALUES (?, ?, ?)";

        if ($this->hideGallery == null) {
            $this->hideGallery = false;
        }

        if ($this->urlCoverGallery == null){
            $this->urlCoverGallery = VariablesConfig::$urlCoverGallery;
        }

        if ($conn->execute_query($sql, [$this->ownerId, $this->name, $this->hideGallery, $this->urlCoverGallery])){
            return true;
        } else {
            $this->setErrorStatus("Error while creating gallery");
            return false;
        }
    }

    /**
     * Function to update gallery info.
     * @return bool
     */
    public function updateGallery() : bool
    {
        $conn = connection();

        $sql = "UPDATE GalleryGroup SET name = ?, hideGallery = ?, urlCoverGallery = ? WHERE galleryId = ?";

        if ($conn->execute_query($sql, [$this->name, $this->hideGallery, $this->galleryId, $this->urlCoverGallery])){
            return true;
        } else {
            $this->setErrorStatus("Error while updating gallery");
            return false;
        }
    }

    /**
     * Function to delete gallery by id.
     * @return bool
     */
    public function deleteGallery() : bool
    {
        $conn = connection();

        $sql = "DELETE FROM GalleryGroup WHERE galleryId = ?";

        if ($conn->execute_query($sql, [$this->galleryId])){
            return true;
        } else {
            $this->setErrorStatus("Error while deleting gallery");
            return false;
        }
    }

    /***/

    /**
     * Function to check if gallery exists by $name and $ownerId.
     * @return bool
     */
    public function checkIfGalleryExists() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM GalleryGroup WHERE ownerId = ? AND name = ?";

        if ($data = $conn->execute_query($sql, [$this->ownerId, $this->name])){
            if ($data->num_rows > 0) {
                return true;
            }
        } else {
            $this->setErrorStatus("Error while checking if gallery exists");
            return false;
        }

        return false;
    }

    /**
     * Function to rename a gallery
     * @param string $galleryName
     * @return bool
     */
    public function renameGallery(string $galleryName) : bool
    {
        $this->setName($galleryName);
        return $this->updateGallery();
    }

    /**
     * Function to hide a gallery
     * @return bool
     */
    public function hideGallery() : bool
    {
        // Run query to hide gallery
        $conn = connection();

        $sql = "UPDATE GalleryGroup SET hideGallery = 1 WHERE galleryId = ?";

        if ($conn->execute_query($sql, [$this->galleryId])){
            return true;
        } else {
            $this->setErrorStatus("Error while hiding gallery");
            return false;
        }
    }

    /**
     * Function to show a gallery
     * @return bool
     */
    public function showGallery() : bool
    {
        // Run query to show gallery
        $conn = connection();

        $sql = "UPDATE GalleryGroup SET hideGallery = 0 WHERE galleryId = ?";

        if ($conn->execute_query($sql, [$this->galleryId])){
            return true;
        } else {
            $this->setErrorStatus("Error while showing gallery");
            return false;
        }
    }

    /**
     * Function to change the cover of a gallery
     * @param string $urlCoverGallery
     * @return bool
     */
    public function changeCoverGallery(string $urlCoverGallery) : bool
    {
        $this->setUrlCoverGallery($urlCoverGallery);
        return $this->updateGallery();
    }

    /**
     * Function to add content to gallery (Please load gallery before).
     * @param int $contentId
     * @return bool
     */
    public function addContentToGallery(int $contentId) : bool
    {
        $conn = connection();

        $sql = "INSERT INTO GalleryAssociation (galleryId, contentId) VALUES (?, ?)";

        if ($conn->execute_query($sql, [$this->galleryId, $contentId])){
            return true;
        } else {
            $this->setErrorStatus("Error while adding content to gallery");
            return false;
        }
    }

    /**
     * Function to remove content from gallery (Please load gallery before).
     * @param int $contentId
     * @return bool
     */
    public function removeContentFromGallery(int $contentId) : bool
    {
        $conn = connection();

        $sql = "DELETE FROM GalleryAssociation WHERE galleryId = ? AND contentId = ?";

        if ($conn->execute_query($sql, [$this->galleryId, $contentId])){
            return true;
        } else {
            $this->setErrorStatus("Error while removing content from gallery");
            return false;
        }
    }

    /**
     * Function to check if $contentId is in gallery (Please load gallery before).
     */
    public function isContentInGallery(int $contentId) : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM GalleryAssociation WHERE galleryId = ? AND contentId = ?";

        if ($data = $conn->execute_query($sql, [$this->galleryId, $contentId])){
            if ($data->num_rows > 0) {
                return true;
            }
        } else {
            $this->setErrorStatus("Error while checking if content is in gallery");
            return false;
        }
        return false;
    }

    /**
     * Function to get all content from gallery (Please load gallery before).
     * @return Content[]
     */
    public function getContent() : array
    {
        $conn = connection();

        $sql = "SELECT distinct GA.contentId FROM GalleryAssociation AS GA INNER JOIN Content AS C on GA.contentId = C.contentId WHERE GA.galleryId = ? ORDER BY C.uploadDate DESC, C.contentId DESC";

        $content = array();
        if ($data = $conn->execute_query($sql, [$this->galleryId])){
            foreach ($data as $row) {
                $tempContent = new Content();
                $tempContent->setContentId($row["contentId"]);
                if ($tempContent->loadContent()) {
                    $content[] = $tempContent;
                }
            }
        }

        // If the array is empty set error status
        if (empty($content)) {
            $this->setErrorStatus("There are no content to show");
        }

        return $content;
    }

    // Getters and Setters
    public function getGalleryId() : int
    {
        return $this->galleryId;
    }

    public function setGalleryId($galleryId)
    {
        $this->galleryId = $galleryId;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getUrlCoverGallery()
    {
        return $this->urlCoverGallery;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getHideGallery()
    {
        return $this->hideGallery;
    }

    public function isHiddenGallery()
    {
        return $this->hideGallery;
    }

    public function setHideGallery($hideGallery)
    {
        $this->hideGallery = $hideGallery;
    }

    public function getErrorStatus()
    {
        return $this->errorStatus;
    }

    public function setErrorStatus($errorStatus)
    {
        $this->errorStatus = $errorStatus;
    }

    public function setUrlCoverGallery($urlCoverGallery)
    {
        $this->urlCoverGallery = $urlCoverGallery;
    }

    // Implements JsonSerializable
    public function jsonSerialize()
    {
        return [
            'galleryId' => $this->galleryId,
            'ownerId' => $this->ownerId,
            'name' => $this->name,
            'hideGallery' => $this->hideGallery,
            'errorStatus' => $this->errorStatus
        ];
    }
}