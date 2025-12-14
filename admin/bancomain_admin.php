<?php
require_once '../config/verificar_admin.php';
include("../config/db_connect.php");
// filtros da questão
$disciplina = $_GET['disciplina'] ?? '';
$conteudo = $_GET['conteudo'] ?? '';
$fonte = $_GET['fonte'] ?? '';

$where = [];

if (!empty($disciplina)) {
    $where[] = "disciplina = '".mysqli_real_escape_string($conn, $disciplina)."'";
}

if (!empty($conteudo)) {
    $where[] = "conteudo LIKE '%".mysqli_real_escape_string($conn, $conteudo)."%'"; 
}

if (!empty($fonte)) {
    $where[] = "fonte LIKE '%".mysqli_real_escape_string($conn, $fonte)."%'"; 
}

$resultado = null;

if (!empty($disciplina) || !empty($conteudo) || !empty($fonte)) {
    $sql = "SELECT * FROM questoes";
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY id ASC";
    $resultado = mysqli_query($conn, $sql);
}

$disciplinas_res = mysqli_query($conn, "SELECT DISTINCT disciplina FROM questoes ORDER BY disciplina ASC");

$disciplinas = [];
while($row = mysqli_fetch_assoc($disciplinas_res)) {
    $disciplinas[] = $row['disciplina'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<title>Buscar Questões - Decola Militar</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- CSS GLOBAL -->
<link rel="stylesheet" href="../stylesheet/navbar.css">
<link rel="stylesheet" href="../stylesheet/footer.css">
<link rel="stylesheet" href="../stylesheet/bancomain.css">
<link rel="stylesheet" href="../stylesheet/global.css">

<!-- MATHJAX -->
<script>
window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] }, svg: { fontCache: 'global' } };
</script>
<script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

<style>
/* ====== ESTÉTICA REPRODUZIDA DO responder_questao.php ====== */

.card-custom {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 0 15px rgba(0,0,0,0.15);
}

.titulo {
    text-align: center;
    font-family: "Black Han Sans", sans-serif;
    margin-top: 30px;
    margin-bottom: 30px;
    color: #2e3d2f;
}

.titulo h1 {
    font-size: 2.6rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.12);
}

/* inputs */
.form-control, .form-select {
    border-radius: 12px;
    padding: 12px;
    font-size: 1rem;
    border: 2px solid #e0e0e0;
}

.form-control:focus, .form-select:focus {
    border-color: #5aaa6a;
    box-shadow: 0 0 8px rgba(90,170,106,0.4);
}

/* botão buscar */
.btn-buscar {
    border-radius: 50px;
    padding: 10px 35px;
    font-size: 1.1rem;
    font-weight: bold;
    box-shadow: 0 0 8px rgba(0,0,0,0.15);
    transition: 0.2s;
}

.btn-buscar:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.20);
}
</style>
</head>

<body>
<?php include("navbar_admin.php"); ?>

<!-- animação bg -->
<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="container mt-5 mb-5">

    <div class="row justify-content-center">
        <div class="col-md-8">

                <div class="titulo">
                    <h1>BANCO DE QUESTOES</h1>
                </div>


                <!-- filtros -->
                <form method="GET" action="exibir_questoes.php" class="mb-4">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Disciplina</label>
                        <select name="disciplina" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach($disciplinas as $d): ?>
                                <option value="<?= $d ?>" <?= $d == $disciplina ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Conteúdo</label>
                        <input type="text" name="conteudo" class="form-control" value="<?= htmlspecialchars($conteudo) ?>" placeholder="Palavra-chave">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Fonte</label>
                        <input type="text" name="fonte" class="form-control" value="<?= htmlspecialchars($fonte) ?>" placeholder="Ex: Enem, Olimpíada...">
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-buscar">Buscar</button>
                    </div>

                </form>


        </div>
    </div>

</div>

<?php include("footer.php"); ?>
</body>
</html>