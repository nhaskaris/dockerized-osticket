<?php
$phar = new Phar('languages/el.phar');
$phar->extractTo('./el_extracted'); // Extracts to a folder named el_extracted
echo "Extraction complete.";
?>