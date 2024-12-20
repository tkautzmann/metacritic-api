<?php 

function removeAcentos($string) {
    $acentos = [
        'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Þ', 'ß',
        'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'þ', 'ÿ'
    ];
    $semAcentos = [
        'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'Th', 's',
        'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'd', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'th', 'y'
    ];

    return str_replace($acentos, $semAcentos, $string);
}


// Configurações de conexão com o banco de dados
$host = 'localhost';
$dbname = 'nintendometro';
$username = 'root';
$password = 'ienh';

try {
    // Cria uma nova conexão PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Configura o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepara e executa a consulta
    $sql = "SELECT id, titulo FROM jogo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Exibe os resultados
    $count = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo htmlspecialchars($row['titulo']) . "\n";
        
        $game_name = htmlspecialchars($row['titulo']);

        $game_name = trim($game_name);

        # convert spaces to -
        $game_name = str_replace(' ', '-', $game_name);

        # Remove &<space>
        $game_name = str_replace('& ', '', $game_name);

        # lowercase
        $game_name = strtolower($game_name);

        $game_name = removeAcentos($game_name);

        $game_name = preg_replace('/[^\p{L}\d\-]/u', '', $game_name);

        $url = "https://www.metacritic.com/game/$game_name/critic-reviews/?platform=nintendo-switch";

        // Obter o HTML do website
        $html = file_get_contents($url);

        if ($html){

            // Criar um objeto DOMDocument
            $dom = new DOMDocument();

            // Carregar o HTML (suprimir warnings com @ caso o HTML não seja bem formatado)
            @$dom->loadHTML($html);

            // Procurar todas as tags específicas (exemplo: `<title>`)

            $titleTags = $dom->getElementsByTagName('meta');

            // Extrair o conteúdo da tag
            foreach ($titleTags as $tag) {
                // mostra os valores dos atributos da tag
                foreach ($tag->attributes as $attribute) {
                    if(strpos($attribute->value, 'score')){
                        preg_match('/score=(\d+)/', $attribute->value, $matches);
                        $metascore = $matches[1];
                        $metacritic_url = $url;
                        $updateSql = "UPDATE jogo SET metascore = :metascore, metacritic_url = :metacritic_url WHERE id = :id";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->execute([
                            ':metascore' => $metascore,
                            ':metacritic_url' => $metacritic_url,
                            ':id' => $row['id']
                        ]);

                    };
                    //echo $attribute->value . "<br>";
                }

            }

        }

        if (++$count >= 20) break;

    }
} catch (PDOException $e) {
    // Trata erros de conexão ou execução
    echo "Erro: " . $e->getMessage();
}

// Encerra a conexão
$pdo = null;