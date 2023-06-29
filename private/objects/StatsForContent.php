<?php
include_once (dirname(__FILE__) . "/../connection.php");

class StatsForContent implements JsonSerializable
{
    private $contentId;
    private $counter;
    private $viewerId;
    private $viewerIP;
    private $dateViewed;
    private $errorStatus;

    /**
     * Function to load specific single stat info from database using $contentId and $viewerId.
     * Please set contentId and viewerId before calling this function.
     * @return bool
     */
    public function loadStatSpecific() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM StatsForContent WHERE contentId = ? AND viewerId = ?";

        if ($data = $conn->execute_query($sql, [$this->contentId, $this->viewerId])){
            if ($data->num_rows <= 0){
                $this->setErrorStatus("Error while loading stat (Not found)");
                return false;
            }
            $row = $data->fetch_assoc();
            $this->setCounter($row["counter"]);
            $this->setViewerId($row["viewerId"]);
            $this->setViewerIP($row["viewerIP"]);
            $this->setDateViewed($row["dateViewed"]);
        } else {
            $this->setErrorStatus("Error while loading stat");
            return false;
        }

        return true;
    }

    /**
     * Function to load all stats info from database using $contentId.
     * Please set contentId before calling this function.
     * @return StatsForContent[]
     */
    public function loadStats() : array
    {
        $conn = connection();

        $sql = "SELECT * FROM StatsForContent WHERE contentId = ?";

        if ($data = $conn->execute_query($sql, [$this->contentId])){
            $stats = [];
            while ($row = $data->fetch_assoc()) {
                $stat = new StatsForContent();
                $stat->setContentId($row["contentId"]);
                $stat->setCounter($row["counter"]);
                $stat->setViewerId($row["viewerId"]);
                $stat->setViewerIP($row["viewerIP"]);
                $stat->setDateViewed($row["dateViewed"]);
                $stats[] = $stat;
            }
        } else {
            $this->setErrorStatus("Error while loading stats");
            return [];
        }

        return $stats;
    }

    /**
     * Function that returns the sum of all stats counter for a contentId.
     * Please set contentId before calling this function.
     * @return int
     */
    public function getSumCounter()
    {
        $conn = connection();

        $sql = "SELECT SUM(counter) AS sumCounter FROM StatsForContent WHERE contentId = ?";

        if ($data = $conn->execute_query($sql, [$this->contentId])){
            $row = $data->fetch_assoc();
            return $row["sumCounter"];
        } else {
            $this->setErrorStatus("Error while loading sum of counter");
            return 0;
        }
    }

    /**
     * Function to add a stat to a content using set contentId, viewerId.
     * Please set contentId, viewerId, viewerIP before calling this function.
     * @return bool
     */
    public function addStat() : bool
    {
        $conn = connection();

        // Checks if viewerIP is set, if not, set it to 0.0.0.0
        if ($this->viewerIP == null){
            $this->setViewerIP("0.0.0.0");
        }

        $sql = "INSERT INTO StatsForContent (contentId, viewerId, viewerIP) VALUES (?, ?, ?)";

        if ($conn->execute_query($sql, [$this->contentId, $this->viewerId, $this->viewerIP])){
            return true;
        } else {
            $this->setErrorStatus("Error while adding stat");
            return false;
        }
    }

    /**
     * Function to update a stat to a content using set contentId, viewerId.
     * Please set contentId, viewerId, viewerIP before calling this function.
     * @return bool
     */
    public function updateStat() : bool
    {
        $conn = connection();

        $sql = "UPDATE StatsForContent SET counter = ?, viewerIP = ?, dateViewed = ? WHERE contentId = ? AND viewerId = ?";

        if ($conn->execute_query($sql, [$this->counter, $this->viewerIP, $this->dateViewed, $this->contentId, $this->viewerId])){
            return true;
        } else {
            $this->setErrorStatus("Error while updating stat");
            return false;
        }
    }

    /**
     * Function to delete a stat to a content using identified by contentId, viewerId.
     * Please set contentId, viewerId before calling this function.
     * @return bool
     */
    public function deleteStat() : bool
    {
        $conn = connection();

        $sql = "DELETE FROM StatsForContent WHERE contentId = ? AND viewerId = ?";

        if ($conn->execute_query($sql, [$this->contentId, $this->viewerId])){
            return true;
        } else {
            $this->setErrorStatus("Error while deleting stat");
            return false;
        }
    }

    /**
     * Function to increment the counter of a stat to a content using identified by contentId, viewerId.
     * This also checks if the stat is found or not, it will add a new stat if not found.
     * It also checks, if found, the dateViewed, if the dateViewed is older than 24 hours, it will update the stat.
     * Please set contentId, viewerId before calling this function.
     * Optionally, you can set viewerIP before calling this function.
     * @return bool
     * @throws Exception
     */
    public function incrementCounter() : bool
    {
        if ($this->isStatFound()){
            $this->loadStatSpecific();
            $dateViewed = new DateTime($this->getDateViewed());
            $dateNow = new DateTime();
            $diff = $dateViewed->diff($dateNow);
            if ($diff->h >= 24){
                $this->setCounter($this->getCounter() + 1);
                $this->setDateViewed($dateNow->format("Y-m-d H:i:s"));
                if ($this->updateStat()){
                    return true;
                } else {
                    return false;
                }
            } else {
                $this->setErrorStatus("Stat is found but dateViewed is not older than 24 hours");
                return false;
            }
        } else {
            if ($this->addStat()){
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Function that returns true if found stat using contentId and viewerId.
     * Please set contentId, viewerId before calling this function.
     * @return bool
     */
    public function isStatFound() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM StatsForContent WHERE contentId = ? AND viewerId = ?";

        if ($data = $conn->execute_query($sql, [$this->contentId, $this->viewerId])){
            if ($data->num_rows <= 0){
                return false;
            }
        } else {
            $this->setErrorStatus("Error while checking stat");
            return false;
        }

        return true;
    }

    // Getter and setters.
    public function getContentId()
    {
        return $this->contentId;
    }

    public function setContentId($contentId): void
    {
        $this->contentId = $contentId;
    }

    public function getCounter()
    {
        return $this->counter;
    }

    public function setCounter($counter): void
    {
        $this->counter = $counter;
    }

    public function getViewerId()
    {
        return $this->viewerId;
    }

    public function setViewerId($viewerId): void
    {
        $this->viewerId = $viewerId;
    }

    public function getViewerIP()
    {
        return $this->viewerIP;
    }

    public function setViewerIP($viewerIP): void
    {
        $this->viewerIP = $viewerIP;
    }

    public function getDateViewed()
    {
        return $this->dateViewed;
    }

    public function setDateViewed($dateViewed): void
    {
        $this->dateViewed = $dateViewed;
    }

    public function getErrorStatus()
    {
        return $this->errorStatus;
    }

    public function setErrorStatus($errorStatus): void
    {
        $this->errorStatus = $errorStatus;
    }

    // Serialize to JSON
    public function jsonSerialize()
    {
        return [
            'contentId' => $this->contentId,
            'counter' => $this->counter,
            'viewerId' => $this->viewerId,
            'viewerIP' => $this->viewerIP,
            'dateViewed' => $this->dateViewed,
            'errorStatus' => $this->errorStatus
        ];
    }
}