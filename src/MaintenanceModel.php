<?php

class MaintenanceModel {
  private $pdo;

  public function __construct($pdo) {
    $this->pdo = $pdo;
  }

  /**
   * Get all maintenance records with book details
   */
  public function getAll($limit = null, $offset = 0) {
    $sql = "SELECT 
              m.id,
              m.book_id,
              m.status,
              m.notes,
              m.updated_at,
              b.id as book_id,
              b.title as book_title,
              b.author as book_author
            FROM book_maintenance m
            JOIN books b ON m.book_id = b.id
            ORDER BY m.updated_at DESC";
    
    if ($limit) {
      $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    }

    try {
      $stmt = $this->pdo->query($sql);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("MaintenanceModel::getAll() Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get maintenance records for a specific book
   */
  public function getByBook($book_id) {
    $sql = "SELECT 
              m.id,
              m.book_id,
              m.status,
              m.notes,
              m.updated_at,
              b.title as book_title,
              b.author as book_author
            FROM book_maintenance m
            JOIN books b ON m.book_id = b.id
            WHERE m.book_id = ?
            ORDER BY m.updated_at DESC";

    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute([(int) $book_id]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("MaintenanceModel::getByBook() Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get a single maintenance record by ID
   */
  public function getById($id) {
    $sql = "SELECT 
              m.id,
              m.book_id,
              m.status,
              m.notes,
              m.updated_at,
              b.title as book_title,
              b.author as book_author
            FROM book_maintenance m
            JOIN books b ON m.book_id = b.id
            WHERE m.id = ?";

    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute([(int) $id]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("MaintenanceModel::getById() Error: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Add a new maintenance record
   */
  public function addRecord($book_id, $status, $notes = null) {
    // Validate book exists
    $checkStmt = $this->pdo->prepare("SELECT id FROM books WHERE id = ?");
    $checkStmt->execute([(int) $book_id]);
    if (!$checkStmt->fetch()) {
      throw new Exception("Book ID {$book_id} tidak ditemukan");
    }

    // Validate status
    $validStatuses = ['Good', 'Worn Out', 'Damaged', 'Missing', 'Need Repair', 'Replaced'];
    if (!in_array($status, $validStatuses)) {
      throw new Exception("Status '{$status}' tidak valid. Pilih: " . implode(', ', $validStatuses));
    }

    $sql = "INSERT INTO book_maintenance (book_id, status, notes) VALUES (?, ?, ?)";

    try {
      $stmt = $this->pdo->prepare($sql);
      $result = $stmt->execute([
        (int) $book_id,
        $status,
        $notes ?: null
      ]);
      return $result ? $this->pdo->lastInsertId() : false;
    } catch (Exception $e) {
      error_log("MaintenanceModel::addRecord() Error: " . $e->getMessage());
      throw new Exception("Gagal menambah catatan maintenance: " . $e->getMessage());
    }
  }

  /**
   * Update maintenance record
   */
  public function updateRecord($id, $status, $notes = null) {
    // Validate status
    $validStatuses = ['Good', 'Worn Out', 'Damaged', 'Missing', 'Need Repair', 'Replaced'];
    if (!in_array($status, $validStatuses)) {
      throw new Exception("Status '{$status}' tidak valid. Pilih: " . implode(', ', $validStatuses));
    }

    $sql = "UPDATE book_maintenance SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?";

    try {
      $stmt = $this->pdo->prepare($sql);
      $result = $stmt->execute([
        $status,
        $notes ?: null,
        (int) $id
      ]);
      return $result ? $stmt->rowCount() : false;
    } catch (Exception $e) {
      error_log("MaintenanceModel::updateRecord() Error: " . $e->getMessage());
      throw new Exception("Gagal update catatan maintenance: " . $e->getMessage());
    }
  }

  /**
   * Delete maintenance record
   */
  public function deleteRecord($id) {
    $sql = "DELETE FROM book_maintenance WHERE id = ?";

    try {
      $stmt = $this->pdo->prepare($sql);
      $result = $stmt->execute([(int) $id]);
      return $result ? $stmt->rowCount() : false;
    } catch (Exception $e) {
      error_log("MaintenanceModel::deleteRecord() Error: " . $e->getMessage());
      throw new Exception("Gagal hapus catatan maintenance: " . $e->getMessage());
    }
  }

  /**
   * Get count of maintenance records
   */
  public function getCount() {
    try {
      $stmt = $this->pdo->query("SELECT COUNT(*) FROM book_maintenance");
      return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
      error_log("MaintenanceModel::getCount() Error: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Get list of books for dropdown
   */
  public function getBooks() {
    $sql = "SELECT id, title, author FROM books ORDER BY title ASC";

    try {
      $stmt = $this->pdo->query($sql);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("MaintenanceModel::getBooks() Error: " . $e->getMessage());
      return [];
    }
  }
}
?>
