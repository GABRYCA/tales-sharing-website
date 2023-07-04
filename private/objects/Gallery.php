<?php
include_once (dirname(__FILE__) . "/../connection.php");
include_once (dirname(__FILE__) . "/../objects/Content.php");

class Gallery implements JsonSerializable
{
    private $galleryId = null;
    private $ownerId = null;
    private $name = null;
    private $hideGallery = false;
    private $errorStatus = null;

    /**
     * Function to load gallery info from database following $galleryId and $ownerId.
     * @return bool
     */
    public function loadGalleryInfoByGalleryId() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM GalleryGroup WHERE galleryId = ? AND ownerId = ? ORDER BY galleryId DESC";

        if ($data = $conn->execute_query($sql, [$this->galleryId, $this->ownerId])){
            $row = $data->fetch_assoc();
            $this->ownerId = $row["ownerId"];
            $this->name = $row["name"];
            $this->hideGallery = $row["hideGallery"];
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

        if ($conn->execute_query($sql, [$this->ownerId, $this->name, $this->hideGallery])){
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

        $sql = "UPDATE GalleryGroup SET name = ?, hideGallery = ? WHERE galleryId = ?";

        if ($conn->execute_query($sql, [$this->name, $this->hideGallery, $this->galleryId])){
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



    // Getters and Setters
    public function getGalleryId()
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

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getHideGallery()
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