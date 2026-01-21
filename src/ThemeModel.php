<?php
/**
 * ThemeModel
 * Mengelola tema sekolah untuk multi-tenant system
 * Fetch tema berdasarkan school_id
 */

class ThemeModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ambil tema sekolah berdasarkan school_id
     * @param int $school_id
     * @return array|null
     */
    public function getSchoolTheme($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT theme_name, custom_colors, typography
            FROM school_themes
            WHERE school_id = :school_id
            LIMIT 1
        ');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();

        // Default theme jika belum ada
        if (!$result) {
            return [
                'theme_name' => 'light',
                'custom_colors' => null,
                'typography' => null
            ];
        }

        return $result;
    }

    /**
     * Ambil tema by school_id dengan format siap pakai
     * @param int $school_id
     * @return array
     */
    public function getThemeData($school_id)
    {
        $theme = $this->getSchoolTheme($school_id);

        return [
            'theme_name' => $theme['theme_name'] ?? 'light',
            'custom_colors' => json_decode($theme['custom_colors'] ?? '{}', true),
            'typography' => json_decode($theme['typography'] ?? '{}', true)
        ];
    }

    /**
     * Simpan tema untuk sekolah
     * @param int $school_id
     * @param string $theme_name
     * @param array|null $custom_colors
     * @param array|null $typography
     * @return bool
     */
    public function saveSchoolTheme($school_id, $theme_name, $custom_colors = null, $typography = null)
    {
        // Check if exists
        $stmt = $this->pdo->prepare('SELECT id FROM school_themes WHERE school_id = :school_id');
        $stmt->execute(['school_id' => $school_id]);
        $exists = $stmt->fetchColumn();

        $colors_json = $custom_colors ? json_encode($custom_colors) : null;
        $typo_json = $typography ? json_encode($typography) : null;

        if ($exists) {
            // Update
            $stmt = $this->pdo->prepare('
                UPDATE school_themes 
                SET theme_name = :theme_name, 
                    custom_colors = :colors, 
                    typography = :typography,
                    updated_at = NOW()
                WHERE school_id = :school_id
            ');
            return $stmt->execute([
                'theme_name' => $theme_name,
                'colors' => $colors_json,
                'typography' => $typo_json,
                'school_id' => $school_id
            ]);
        } else {
            // Insert
            $stmt = $this->pdo->prepare('
                INSERT INTO school_themes 
                (school_id, theme_name, custom_colors, typography)
                VALUES (:school_id, :theme_name, :colors, :typography)
            ');
            return $stmt->execute([
                'school_id' => $school_id,
                'theme_name' => $theme_name,
                'colors' => $colors_json,
                'typography' => $typo_json
            ]);
        }
    }
}
?>