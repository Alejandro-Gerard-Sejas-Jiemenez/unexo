<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = Schema::getTables();

$script = "var currentModel = Repository.GetProjectInterface();\n";
$script .= "var rootPackage = Repository.GetTreeSelectedPackage();\n";
$script .= "if (rootPackage == null) {\n";
$script .= "    Session.Prompt(\"Selecciona un paquete en el Project Browser primero.\", 0);\n";
$script .= "} else {\n";
$script .= "    var classMap = {};\n"; // Objeto para guardar los IDs de las clases creadas

// Paso 1: Crear las clases y atributos
foreach ($tables as $table) {
    $tableName = $table['name'];
    if (in_array($tableName, ['migrations', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
        continue;
    }
    
    $script .= "    var cls = rootPackage.Elements.AddNew(\"$tableName\", \"Class\");\n";
    $script .= "    cls.Update();\n";
    $script .= "    classMap[\"$tableName\"] = cls.ElementID;\n";
    
    $fks = Schema::getForeignKeys($tableName);
    $fkColumns = [];
    foreach ($fks as $fk) {
        $fkColumns = array_merge($fkColumns, $fk['columns']);
    }
    
    $columns = Schema::getColumns($tableName);
    foreach ($columns as $column) {
        $colName = $column['name'];
        $colType = $column['type_name'];
        
        // Mapeo de tipos de datos
        if (str_contains(strtolower($colType), 'varchar') || str_contains(strtolower($colType), 'text')) {
            $colType = 'String';
        }
        if (str_contains(strtolower($colType), 'int2') || str_contains(strtolower($colType), 'integer')) {
            $colType = 'int';
        }
        
        // No añadir las llaves foráneas como atributos (las relaciones ya lo representan)
        if (in_array($colName, $fkColumns)) {
            continue;
        }
        
        $script .= "    var attr = cls.Attributes.AddNew(\"$colName\", \"$colType\");\n";
        $script .= "    attr.Update();\n";
    }
    $script .= "    rootPackage.Elements.Refresh();\n";
}

function obtenerVerbo($columna) {
    switch ($columna) {
        case 'programa_id': return 'pertenece_a';
        case 'modulo_id': return 'incluye';
        case 'aula_id': return 'ocupa';
        case 'docente_id': return 'impartido_por';
        case 'estudiante_id': return 'realizada_por';
        case 'cohorte_id': return 'asignado_a';
        case 'matricula_id': return 'registra';
        case 'grupo_id': return 'pertenece_a';
        default: return 'asocia';
    }
}

// Paso 2: Crear las relaciones basadas en claves foráneas (Foreign Keys)
foreach ($tables as $table) {
    $tableName = $table['name'];
    if (in_array($tableName, ['migrations', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
        continue;
    }
    
    $fks = Schema::getForeignKeys($tableName);
    foreach ($fks as $fk) {
        $referencedTable = $fk['foreign_table'];
        $foreignCol = implode(', ', $fk['columns']);
        
        $verbo = obtenerVerbo($foreignCol);
        
        // Conectar la clase origen con la clase destino usando el map
        $script .= "    if (classMap[\"$tableName\"] && classMap[\"$referencedTable\"]) {\n";
        $script .= "        var sourceCls = Repository.GetElementByID(classMap[\"$tableName\"]);\n";
        // Añadir una relación de tipo Asociación (puedes cambiar a Dependency si prefieres)
        $script .= "        var connector = sourceCls.Connectors.AddNew(\"$verbo\", \"Association\");\n";
        $script .= "        connector.ClientID = sourceCls.ElementID;\n";
        $script .= "        connector.SupplierID = classMap[\"$referencedTable\"];\n";
        $script .= "        connector.Direction = \"Unspecified\";\n";
        $script .= "        connector.Update();\n";
        
        // Agregar multiplicidad (Cardinalidad)
        $script .= "        var clientEnd = connector.ClientEnd;\n";
        $script .= "        clientEnd.Cardinality = \"0..*\";\n";
        $script .= "        clientEnd.Update();\n";
        
        $script .= "        var supplierEnd = connector.SupplierEnd;\n";
        $script .= "        supplierEnd.Cardinality = \"1\";\n";
        $script .= "        supplierEnd.Update();\n";
        $script .= "        connector.Update();\n";
        $script .= "        sourceCls.Connectors.Refresh();\n";
        $script .= "    }\n";
    }
}

$script .= "    Session.Prompt(\"Clases y relaciones generadas correctamente.\", 0);\n";
$script .= "}\n";

$outputDir = __DIR__ . '/output';
if (!File::exists($outputDir)) {
    File::makeDirectory($outputDir, 0755, true);
}

File::put($outputDir . '/GeneradorBaseDatos.js', $script);

echo "Archivo JScript generado en: $outputDir/GeneradorBaseDatos.js\n";
