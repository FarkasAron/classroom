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
                <button name='action' class='plusbutton' value='classSubjectAverages'>Osztályátlag</button>
                <button name='action' class='plusbutton' value='schoolSubjectAverages'>Iskola átlag</button>
                <button name='action' class='plusbutton' value='rankings'>Rangsor</button>
                <button name='action' class='plusbutton' value='bestAndWorstClasses'>J/R osztály   </button>
            </form>
            <form method='post' action=''>
                <button name='regenerate_classes' value='true'>Újra</button>
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

    if (isset($_POST['export_class'])) {
        $classToExport = $_POST['export_class'];
        exportClassToCSV($classToExport);
    }
}

function showClassTable($class)
{
    echo "<div class='class-container'>";
    echo "<h3>$class osztály</h3>";
    
    // diákok és jegyeik táblázat
    if (isset($_SESSION['classes'][$class])) {
        echo "<table border='1'>";
        echo "<thead><tr><th>Diák neve</th>";
        
        // Tantárgyak fejléc
        foreach (DATA['subjects'] as $subject) {
            echo "<th>$subject</th>";
        }
        
        echo "</tr></thead><tbody>";
        
        foreach ($_SESSION['classes'][$class] as $student) {
            echo "<tr>";
            echo "<td>" . $student['name'] . "</td>";
            
            // Jegyek minden tantárgyra
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
    fputcsv($file, $header, ";");

    //  Diákok adathozzáadás
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
            fputcsv($file, $row, ";");
        }
    }

    fclose($file);

    echo "<p>A $class osztály adatai sikeresen elmentve ide: $filePath</p>";
}


if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'classSubjectAverages':
            $averages = calculateClassSubjectAverages();
            displayAverages($averages, 'Class Subject Averages');
            break;
        case 'schoolSubjectAverages':
            $averages = calculateSchoolSubjectAverages();
            displayAverages($averages, 'School Subject Averages');
            break;
        case 'rankings':
            $rankings = calculateRankings();
            displayRankings($rankings);
            break;
        case 'bestAndWorstClasses':
            $results = calculateBestAndWorstClasses();
            displayBestAndWorstClasses($results);
            break;
        case 'export':
            $dataType = $_POST['dataType'];
            exportToCSV($dataType);
            break;
    }
}

function calculateClassSubjectAverages()
{
    $classAverages = [];
    foreach ($_SESSION['classes'] as $class => $students) {
        $classAverages[$class] = [];
        foreach (DATA['subjects'] as $subject) {
            $total = 0;
            $count = 0;
            foreach ($students as $student) {
                if (!empty($student['grades'][$subject])) {
                    $total += array_sum($student['grades'][$subject]);
                    $count += count($student['grades'][$subject]);
                }
            }
            $classAverages[$class][$subject] = $count > 0 ? $total / $count : 0;
        }
    }
    return $classAverages;
}

function calculateSchoolSubjectAverages()
{
    $schoolAverages = [];
    foreach (DATA['subjects'] as $subject) {
        $total = 0;
        $count = 0;
        foreach ($_SESSION['classes'] as $students) {
            foreach ($students as $student) {
                if (!empty($student['grades'][$subject])) {
                    $total += array_sum($student['grades'][$subject]);
                    $count += count($student['grades'][$subject]);
                }
            }
        }
        $schoolAverages[$subject] = $count > 0 ? $total / $count : 0;
    }
    return $schoolAverages;
}

function calculateRankings()
{
    $rankings = [];
    foreach ($_SESSION['classes'] as $class => $students) {
        $rankings[$class] = [];
        foreach ($students as $student) {
            $totalGrades = 0;
            $gradeCount = 0;
            foreach ($student['grades'] as $subjectGrades) {
                $totalGrades += array_sum($subjectGrades);
                $gradeCount += count($subjectGrades);
            }
            $average = $gradeCount > 0 ? $totalGrades / $gradeCount : 0;
            $rankings[$class][] = [
                'name' => $student['name'],
                'average' => $average
            ];
        }
        usort($rankings[$class], function ($a, $b) {
            return $b['average'] <=> $a['average'];
        });
    }
    return $rankings;
}

function calculateBestAndWorstClasses()
{
    $classAverages = calculateClassSubjectAverages();
    $totalAverages = [];
    foreach ($classAverages as $class => $subjects) {
        $total = array_sum($subjects);
        $count = count($subjects);
        $totalAverages[$class] = $count > 0 ? $total / $count : 0;
    }
    arsort($totalAverages);
    return [
        'best' => array_key_first($totalAverages),
        'worst' => array_key_last($totalAverages),
    ];
}

function displayAverages($averages, $title)
{
    echo "<h2>$title</h2><table border='1'><tr><th>Class/Subject</th><th>Average</th></tr>";
    foreach ($averages as $key => $values) {
        if (is_array($values)) {
            foreach ($values as $subject => $average) {
                echo "<tr><td>$key - $subject</td><td>" . number_format($average, 2) . "</td></tr>";
            }
        } else {
            echo "<tr><td>$key</td><td>" . number_format($values, 2) . "</td></tr>";
        }
    }
    echo "</table>";
}

function displayRankings($rankings)
{
    echo "<h2>Student Rankings</h2>";
    foreach ($rankings as $class => $students) {
        echo "<h3>Class: $class</h3><table border='1'><tr><th>Rank</th><th>Student</th><th>Average</th></tr>";
        foreach ($students as $index => $student) {
            echo "<tr><td>" . ($index + 1) . "</td><td>{$student['name']}</td><td>" . number_format($student['average'], 2) . "</td></tr>";
        }
        echo "</table>";
    }
}

function displayBestAndWorstClasses($results)
{
    echo "<h2>Legjobb és legrosszabb osztályok:</h2>";
    echo "<p>Legjobb osztály: {$results['best']}</p>";
    echo "<p>Legrosszabb osztály: {$results['worst']}</p>";
}

function exportToCSV($dataType)
{
    $exportDir = __DIR__ . '/export';
    if (!file_exists($exportDir) || !is_dir($exportDir)) {
        mkdir($exportDir, 0777, true);
    }
    $timestamp = date('Y-m-d_His');
    $filePath = "$exportDir/$dataType-$timestamp.csv";
    $file = fopen($filePath, 'w');
    if (!$file) {
        echo "<p>Error opening file for export.</p>";
        return;
    }

    switch ($dataType) {
        case 'classSubjectAverages':
            $data = calculateClassSubjectAverages();
            $header = ['Class', 'Subject', 'Average'];
            fputcsv($file, $header, ";");
            foreach ($data as $class => $subjects) {
                foreach ($subjects as $subject => $average) {
                    fputcsv($file, [$class, $subject, $average], ";f");
                }
            }
            break;
        // Add other cases for exporting rankings or best/worst classes
    }

    fclose($file);
    echo "<p>Data exported to $filePath</p>";
}

?>
