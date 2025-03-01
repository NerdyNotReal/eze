<?php
// Directory where your CSS files are stored
$cssDir = '../public/css';
foreach (glob($cssDir . "/*.css") as $cssFile) {

    echo '<link rel="stylesheet" href="' . $cssFile . '">' . PHP_EOL;
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
}



// // Directory where your JS files are stored
// $jsDir = '../public/js';
// foreach (glob($jsDir . "/*.js") as $jsFile) {

//     echo '<script src="' . $jsFile . '" type="module" defer></script>' . PHP_EOL;    
// }

?>
