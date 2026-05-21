<?php
class PassengerService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getPassengerByPNR($pnr) {
        $stmt = $this->db->prepare(
            "SELECT * FROM passengers WHERE pnr_code = ?"
        );
        $stmt->execute([$pnr]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateCheckInStatus($passengerId, $status) {
        $stmt = $this->db->prepare(
            "UPDATE passengers SET checkin_status = ? WHERE id = ?"
        );
        return $stmt->execute([$status, $passengerId]);
    }
}