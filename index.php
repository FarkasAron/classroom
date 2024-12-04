<?php
session_start();
include 'classroom-data.php';
echo '<link rel="stylesheet" href="style.css">';

showChooser();
showSelectedClass();
createClasses();
createGrades();

function createClasses()
{
    if (!isset($_SESSION['classes']) || isset($_POST['regenerate_classes'])) { // Új osztályok generálása, ha a gombot megnyomják
        $_SESSION['classes'] = []; // Az osztályok törlése
        foreach (DATA['classes'] as $class) {
            $_SESSION['classes'][$class] = [];
            $numberOfStudents = rand(10, 15);
            for ($i = 0; $i < $numberOfStudents; $i++) {
                $name = "";
                $name .= DATA['lastnames'][rand(0, count(DATA['lastnames']) - 1)] . " ";
                if (rand(0, 1) == 0) {
                    $gender = 'man';
                    $name .= DATA['firstnames']['men'][rand(0, count(DATA['firstnames']['men']) - 1)];
                } else {
                    $gender = 'woman';
                    $name .= DATA['firstnames']['women'][rand(0, count(DATA['firstnames']['women']) - 1)];
                }

                $student = [
                    'name' => $name,
                    'gender' => $gender,
                    'grades' => createGrades()
                ];

                $_SESSION['classes'][$class][] = $student;
            }
        }
    }
}

function createGrades()
{
    $grades = [];
    foreach (DATA['subjects'] as $subject) {
        $grades[$subject] = [];
        $numberOfGrades = rand(0, 5);
        for ($i = 0; $i < $numberOfGrades; $i++) {
            $grades[$subject][] = rand(1, 5);
        }
    }
    return $grades;
}

function showChooser()
{
    echo "
    <div id='kulso'>
        <h1>Válasszon egy osztályt!<h2>
        <div id='chooser'>
            <form method='post' action=''>
                <button name='class' value='all'>Összes</button>
            </form>
            <form method='post' action=''>
                <button name='class' value='11a'>11. A</button>
                <button name='class' value='11b'>11. B</button>
                <button name='class' value='11c'>11. C</button>
                <button name='class' value='12a'>12. A</button>
                <button name='class' value='12b'>12. B</button>
                <button name='class' value='12c'>12. C</button>
            </form>
            <form method='post' action=''>
                <button name='regenerate_classes' value='true'>Újra generálás</button>
            </form>
        </div>
    </div>";
}

function showSelectedClass()
{
    if (isset($_POST['class'])) {
        $selectedClass = $_POST['class'];

        echo "<h2>Diákok az $selectedClass osztályban:</h2>";

        if ($selectedClass == 'all') {
            showClassTable('11a');
            showClassTable('11b');
            showClassTable('11c');
            showClassTable('12a');
            showClassTable('12b');
            showClassTable('12c');
        } else {
            showClassTable($selectedClass);

            // Gomb az osztály adatainak mentéséhez
            echo "
            <form method='post' action=''>
                <input type='hidden' name='export_class' value='$selectedClass'>
                <button type='submit'>Letöltés</button>
            </form>";
        }
    }

    // Csak akkor hívjuk meg az exportálás funkciót, ha a letöltés gombra kattintottak
    if (isset($_POST['export_class'])) {
        $classToExport = $_POST['export_class'];
        exportClassToCSV($classToExport);
    }
}

function showClassTable($class)
{
    echo "<div class='class-container'>";
    echo "<h3>$class osztály</h3>";
    
    // Osztály diákjainak és jegyeik táblázata
    if (isset($_SESSION['classes'][$class])) {
        echo "<table border='1'>";
        echo "<thead><tr><th>Diák neve</th>";
        
        // Tantárgyak fejlécének megjelenítése
        foreach (DATA['subjects'] as $subject) {
            echo "<th>$subject</th>";
        }
        
        echo "</tr></thead><tbody>";
        
        foreach ($_SESSION['classes'][$class] as $student) {
            echo "<tr>";
            echo "<td>" . $student['name'] . "</td>";
            
            // Jegyek megjelenítése minden tantárgyra
            foreach (DATA['subjects'] as $subject) {
                echo "<td>";
                
                if (isset($student['grades'][$subject])) {
                    foreach ($student['grades'][$subject] as $grade) {
                        echo "$grade ";
                    }
                } else {
                    echo "Nincs jegy";
                }
                
                echo "</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>Nincsenek diákok az osztályban.</p>";
    }

    echo "</div>";
}

//----------------------ADAT MENTÉS--------------------------

function exportClassToCSV($class)
{
    // 'export' mappa ellenőrzése és létrehozása
    $exportDir = __DIR__ . '/export';
    if (!file_exists($exportDir) || !is_dir($exportDir)) {
        mkdir($exportDir, 0777, true);
    }

    // Időbélyeg hozzáadása a fájl nevéhez
    $timestamp = date('Y-m-d_His');
    $filename = "$class-$timestamp.csv";
    $filePath = $exportDir . '/' . $filename;

    // CSV fájl megnyitása írásra
    $file = fopen($filePath, 'w');

    if (!$file) {
        echo "<p>Hiba történt a fájl megnyitásakor.</p>";
        return;
    }

    // Fejléc hozzáadása a fájlhoz
    $header = ['Diák neve'];
    foreach (DATA['subjects'] as $subject) {
        $header[] = $subject;
    }
    fputcsv($file, $header);

    // Diákok adatainak hozzáadása
    if (isset($_SESSION['classes'][$class])) {
        foreach ($_SESSION['classes'][$class] as $student) {
            $row = [$student['name']];
            foreach (DATA['subjects'] as $subject) {
                if (isset($student['grades'][$subject])) {
                    $row[] = implode(' ', $student['grades'][$subject]); // Jegyek szóközzel elválasztva
                } else {
                    $row[] = 'Nincs jegy';
                }
            }
            fputcsv($file, $row);
        }
    }

    fclose($file);

    echo "<p>A $class osztály adatai sikeresen elmentve ide: $filePath</p>";
}

?>
