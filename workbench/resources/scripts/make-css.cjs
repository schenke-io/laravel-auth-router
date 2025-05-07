const fs = require('fs');
const path = require('path');
const CleanCSS = require('clean-css');

// Konfiguration
const inputCssFile = 'resources/css/style.css'; // Pfad zu Ihrer manuellen CSS-Datei
const outputBladeFile = 'resources/views/helper/style.blade.php'; // Zielpfad für die Blade-Datei

const inputPath = path.join(__dirname, '..','..', inputCssFile);
const outputPath = path.join(__dirname, '..', '..','..',outputBladeFile);
const outputDir = path.dirname(outputPath);

// Instanz von CleanCSS erstellen (Optionen können hier konfiguriert werden, standardmäßig gut)
const cleanCss = new CleanCSS({});

console.log(`Processing CSS file: ${inputPath}`);
console.log(`Output Blade file: ${outputPath}`);

// Überprüfen, ob die Eingabedatei existiert
if (!fs.existsSync(inputPath)) {
    console.error(`Error: Input CSS file not found at ${inputPath}`);
    process.exit(1); // Skript mit Fehler beenden
}

try {
    // CSS-Inhalt lesen
    const cssContent = fs.readFileSync(inputPath, 'utf8');

    // CSS-Inhalt komprimieren/minifizieren
    const minifiedCssContent = cleanCss.minify(cssContent).styles;

    // Inhalt mit <style>-Tags umwickeln (jetzt den minifizierten Inhalt verwenden)
    const bladeContent = `<style>
${minifiedCssContent}
</style>`;

    // Sicherstellen, dass das Zielverzeichnis existiert
    if (!fs.existsSync(outputDir)) {
        console.log(`Creating output directory: ${outputDir}`);
        fs.mkdirSync(outputDir, { recursive: true });
    }

    // Blade-Datei schreiben
    fs.writeFileSync(outputPath, bladeContent, 'utf8');

    console.log(`Successfully created and minified CSS into ${outputPath}`);

} catch (error) {
    console.error('An error occurred:', error);
    process.exit(1); // Skript mit Fehler beenden
}