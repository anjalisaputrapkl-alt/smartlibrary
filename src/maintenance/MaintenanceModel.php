<?php

class MaintenanceModel
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * Get all maintenance records with book details for a specific school
   */
  public function getAll($school_id, $limit = null, $offset = 0)
  {
    $sql = "SELECT 
              m.id,
              m.book_id,
              m.status,
              m.notes,
              m.priority,
              m.follow_up_date,
              m.updated_at,
              b.id as book_id,
              b.title as book_title,
              b.author as book_author
            FROM book_maintenance m
            JOIN books b ON m.book_id = b.id
            WHERE b.school_id = :school_id
            ORDER BY m.updated_at DESC";

    if ($limit) {
      $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    }

    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(['school_id' => (int) $school_id]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("MaintenanceModel::getAll() Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get maintenance records for a specific book in a school
   */
  public function getByBook($school_id, $book_id)
  {
    $sql = "SELECT 
              m.id,
              m.book_id,
              m.status,
              m.notes,
              m.priority,
              m.follow_up_date,
              m.updated_at,
              b.title as book_title,
              b.author as book_author
            FROM book_maintenance m
            JOIN books b ON m.book_id = b.id
            WHERE m.book_id = :book_id AND b.school_id = :school_id
            ORDER BY m.updated_at DESC";

    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(['book_id' => (int) $book_id, 'school_id' => (int) $school_id]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("MaintenanceModel::getByBook() Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get a single maintenance record by ID for a specific school
   */
  public function getById($school_id, $id)
  {
    $sql = "SELECT 
              m.id,
              m.book_id,
              m.status,
              m.notes,
              m.priority,
              m.follow_up_date,
              m.updated_at,
              b.title as book_title,
              b.author as book_author
            FROM book_maintenance m
            JOIN books b ON m.book_id = b.id
            WHERE m.id = :id AND b.school_id = :school_id";

    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(['id' => (int) $id, 'school_id' => (int) $school_id]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("MaintenanceModel::getById() Error: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Add a new maintenance record for a school
   */
  public function addRecord($school_id, $book_id, $status, $priority = 'Normal', $notes = null, $follow_up_date = null)
  {
    // Validate book exists and belongs to this school
    $checkStmt = $this->pdo->prepare("SELECT id FROM books WHERE id = :book_id AND school_id = :school_id");
    $checkStmt->execute(['book_id' => (int) $book_id, 'school_id' => (int) $school_id]);
    if (!$checkStmt->fetch()) {
      throw new Exception("Book ID {$book_id} tidak ditemukan atau bukan milik sekolah Anda");
    }

    // Validate status
    $validStatuses = ['Good', 'Worn Out', 'Damaged', 'Missing', 'Need Repair', 'Replaced'];
    if (!in_array($status, $validStatuses)) {
      throw new Exception("Status '{$status}' tidak valid. Pilih: " . implode(', ', $validStatuses));
    }

    $sql = "INSERT INTO book_maintenance (book_id, status, priority, notes, follow_up_date) VALUES (:book_id, :status, :priority, :notes, :follow_up_date)";

    try {
      $stmt = $this->pdo->prepare($sql);
      $result = $stmt->execute([
        ':book_id' => (int) $book_id,
        ':status' => $status,
        ':priority' => $priority,
        ':notes' => $notes ?: null,
        ':follow_up_date' => $follow_up_date ?: null
      ]);
      return $result ? $this->pdo->lastInsertId() : false;
    } catch (Exception $e) {
      error_log("MaintenanceModel::addRecord() Error: " . $e->getMessage());
      throw new Exception("Gagal menambah catatan maintenance: " . $e->getMessage());
    }
  }

  /**
   * Update maintenance record for a school
   */
  public function updateRecord($school_id, $id, $status, $priority = 'Normal', $notes = null, $follow_up_date = null)
  {
    // Validate status
    $validStatuses = ['Good', 'Worn Out', 'Damaged', 'Missing', 'Need Repair', 'Replaced'];
    if (!in_array($status, $validStatuses)) {
      throw new Exception("Status '{$status}' tidak valid. Pilih: " . implode(', ', $validStatuses));
    }

    // Check that record belongs to this school
    $checkStmt = $this->pdo->prepare("SELECT m.id FROM book_maintenance m JOIN books b ON m.book_id = b.id WHERE m.id = :id AND b.school_id = :school_id");
    $checkStmt->execute(['id' => (int) $id, 'school_id' => (int) $school_id]);
    if (!$checkStmt->fetch()) {
      throw new Exception("Catatan maintenance tidak ditemukan atau bukan milik sekolah Anda");
    }

    $sql = "UPDATE book_maintenance SET status = :status, priority = :priority, notes = :notes, follow_up_date = :follow_up_date, updated_at = NOW() WHERE id = :id";

    try {
      $stmt = $this->pdo->prepare($sql);
      $result = $stmt->execute([
        ':status' => $status,
        ':priority' => $priority,
        ':notes' => $notes ?: null,
        ':follow_up_date' => $follow_up_date ?: null,
        ':id' => (int) $id
      ]);
      return $result ? $stmt->rowCount() : false;
    } catch (Exception $e) {
      error_log("MaintenanceModel::updateRecord() Error: " . $e->getMessage());
      throw new Exception("Gagal update catatan maintenance: " . $e->getMessage());
    }
  }

  /**
   * Delete maintenance record for a school
   */
  public function deleteRecord($school_id, $id)
  {
    // Check that record belongs to this school
    $checkStmt = $this->pdo->prepare("SELECT m.id FROM book_maintenance m JOIN books b ON m.book_id = b.id WHERE m.id = :id AND b.school_id = :school_id");
    $checkStmt->execute(['id' => (int) $id, 'school_id' => (int) $school_id]);
    if (!$checkStmt->fetch()) {
      throw new Exception("Catatan maintenance tidak ditemukan atau bukan milik sekolah Anda");
    }

    $sql = "DELETE FROM book_maintenance WHERE id = :id";

    try {
      $stmt = $this->pdo->prepare($sql);
      $result = $stmt->execute([':id' => (int) $id]);
      return $result ? $stmt->rowCount() : false;
    } catch (Exception $e) {
      error_log("MaintenanceModel::deleteRecord() Error: " . $e->getMessage());
      throw new Exception("Gagal hapus catatan maintenance: " . $e->getMessage());
    }
  }

  /**
   * Get count of maintenance records for a school
   */
  public function getCount($school_id)
  {
    try {
      $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM book_maintenance m JOIN books b ON m.book_id = b.id WHERE b.school_id = :school_id");
      $stmt->execute(['school_id' => (int) $school_id]);
      return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
      error_log("MaintenanceModel::getCount() Error: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Get list of books for dropdown from a specific school
   */
  public function getBooks($school_id)
  {
    $sql = "SELECT id, title, author FROM books WHERE school_id = :school_id ORDER BY title ASC";

    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(['school_id' => (int) $school_id]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log("MaintenanceModel::getBooks() Error: " . $e->getMessage());
      return [];
    }
  }
}
?>