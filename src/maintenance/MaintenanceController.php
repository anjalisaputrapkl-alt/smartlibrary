<?php

require_once __DIR__ . '/MaintenanceModel.php';

class MaintenanceController
{
  private $model;
  private $school_id;

  public function __construct($pdo, $school_id)
  {
    $this->model = new MaintenanceModel($pdo);
    $this->school_id = (int) $school_id;
  }

  /**
   * Get all maintenance records for the school
   */
  public function getAll($limit = null, $offset = 0)
  {
    return $this->model->getAll($this->school_id, $limit, $offset);
  }

  /**
   * Get maintenance records for a specific book in this school
   */
  public function getByBook($book_id)
  {
    return $this->model->getByBook($this->school_id, $book_id);
  }

  /**
   * Get a single record by ID for this school
   */
  public function getById($id)
  {
    return $this->model->getById($this->school_id, $id);
  }

  /**
   * Add new maintenance record for this school
   */
  public function addRecord($book_id, $status, $priority = 'Normal', $notes = null, $follow_up_date = null)
  {
    try {
      $id = $this->model->addRecord($this->school_id, $book_id, $status, $priority, $notes, $follow_up_date);
      return [
        'success' => true,
        'message' => 'Catatan maintenance berhasil ditambahkan',
        'id' => $id
      ];
    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Update maintenance record for this school
   */
  public function updateRecord($id, $status, $priority = 'Normal', $notes = null, $follow_up_date = null)
  {
    try {
      $result = $this->model->updateRecord($this->school_id, $id, $status, $priority, $notes, $follow_up_date);
      if ($result) {
        return [
          'success' => true,
          'message' => 'Catatan maintenance berhasil diupdate'
        ];
      } else {
        return [
          'success' => false,
          'message' => 'Tidak ada data yang diupdate'
        ];
      }
    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Delete maintenance record for this school
   */
  public function deleteRecord($id)
  {
    try {
      $result = $this->model->deleteRecord($this->school_id, $id);
      if ($result) {
        return [
          'success' => true,
          'message' => 'Catatan maintenance berhasil dihapus'
        ];
      } else {
        return [
          'success' => false,
          'message' => 'Catatan tidak ditemukan'
        ];
      }
    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Get count of records for this school
   */
  public function getCount()
  {
    return $this->model->getCount($this->school_id);
  }

  /**
   * Get list of books for this school
   */
  public function getBooks()
  {
    return $this->model->getBooks($this->school_id);
  }

  /**
   * Handle AJAX requests
   */
  public function handleAjax()
  {
    // Bersihkan output buffer
    if (ob_get_level() > 0) {
      ob_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    $action = $_POST['action'] ?? $_GET['action'] ?? null;

    if (!$action) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Action tidak didefinisikan']);
      exit;
    }

    try {
      switch ($action) {
        case 'add':
          $book_id = $_POST['book_id'] ?? null;
          $status = $_POST['status'] ?? null;
          $priority = $_POST['priority'] ?? 'Normal';
          $notes = $_POST['notes'] ?? null;
          $follow_up_date = $_POST['follow_up_date'] ?? null;

          if (!$book_id || !$status) {
            throw new Exception('Book ID dan Status harus diisi');
          }

          $result = $this->addRecord($book_id, $status, $priority, $notes, $follow_up_date);
          echo json_encode($result);
          break;

        case 'update':
          $id = $_POST['id'] ?? null;
          $status = $_POST['status'] ?? null;
          $priority = $_POST['priority'] ?? 'Normal';
          $notes = $_POST['notes'] ?? null;
          $follow_up_date = $_POST['follow_up_date'] ?? null;

          if (!$id || !$status) {
            throw new Exception('ID dan Status harus diisi');
          }

          $result = $this->updateRecord($id, $status, $priority, $notes, $follow_up_date);
          echo json_encode($result);
          break;

        case 'delete':
          $id = $_POST['id'] ?? null;

          if (!$id) {
            throw new Exception('ID harus diisi');
          }

          $result = $this->deleteRecord($id);
          echo json_encode($result);
          break;

        case 'get':
          $id = $_GET['id'] ?? null;
          $book_id = $_GET['book_id'] ?? null;

          if ($id) {
            $data = $this->getById($id);
          } elseif ($book_id) {
            $data = $this->getByBook($book_id);
          } else {
            $data = $this->getAll();
          }

          echo json_encode(['success' => true, 'data' => $data]);
          break;

        default:
          http_response_code(400);
          echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
      }
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
  }
}
?>