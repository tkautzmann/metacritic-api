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
$username = 'adm_nintendometro';
$password = 'ienh';

try {
    // Cria uma nova conexão PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Configura o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepara e executa a consulta
    $sql = "SELECT id, titulo, metascore FROM jogo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Exibe os resultados
    // $count = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        
        if($row['metascore'] != null) continue;

        // $game_name = htmlspecialchars($row['titulo']);
        $game_name = $row['titulo'];

        $game_name = trim($game_name);

        # lowercase
        $game_name = strtolower($game_name);

        $game_name = removeAcentos($game_name);

        $game_name = str_replace('&', ' and ', $game_name);
        $game_name = preg_replace('/\+$/', '', $game_name);
        $game_name = str_replace('+', ' plus ', $game_name);
        $game_name = preg_replace('/@$/', '', $game_name);
        $game_name = preg_replace('/™$/', '', $game_name);
        $game_name = preg_replace('/ for Nintendo Switch$/', '', $game_name);
        $game_name = str_replace('™ ', ' ', $game_name);
        $game_name = str_replace('™', '', $game_name);
        $game_name = str_replace(' - ', ' ', $game_name);
        $game_name = str_replace(' : ', ' ', $game_name);
        $game_name = str_replace(' – ', ' ', $game_name);
        $game_name = str_replace('® ', ' ', $game_name);
        $game_name = str_replace('®', '', $game_name);
        $game_name = str_replace(' for Nintendo Switch', '', $game_name);
        $game_name = str_replace('  ', ' ', $game_name);
        $game_name = preg_replace('/[^\p{L}\d \-]/u', '', $game_name);
        $game_name = str_replace(' ', '-', $game_name);

        echo htmlspecialchars($row['titulo']) . "\n";
        echo $game_name . "\n";

        
        $url = "https://www.metacritic.com/game/$game_name/critic-reviews/?platform=nintendo-switch";

        // Obter o HTML do website
        @$html = file_get_contents($url);

        if ($html){

            // Criar um objeto DOMDocument
            $dom = new DOMDocument();

            // Carregar o HTML (suprimir warnings com @ caso o HTML não seja bem formatado)
            @$dom->loadHTML($html);

            // Procurar todas as tags específicas (exemplo: `<title>`)

            $titleTags = $dom->getElementsByTagName("div");

            $achou = false;

            // Extrair o conteúdo da tag
            foreach ($titleTags as $tag) {
                foreach ($tag->attributes as $attribute) {
                    if($attribute->nodeName == 'title'){
                        if(trim($attribute->nodeValue) != ''){
                            if (preg_match('/Metascore (\d+)/', $attribute->nodeValue, $matches)) {
                                $metascore = $matches[1];
                                $metacritic_url = $url;
                                $updateSql = "UPDATE jogo SET metascore = :metascore, metacritic_url = :metacritic_url WHERE id = :id";
                                $updateStmt = $pdo->prepare($updateSql);
                                $updateStmt->execute([
                                    ':metascore' => $metascore,
                                    ':metacritic_url' => $metacritic_url,
                                    ':id' => $row['id']
                                ]);
                                echo "INSERIU O GAME";
                                echo "\n";
                            }
                            $achou = true;
                            break;
                        }
                    };
                }
                if($achou){
                    break;
                }
            }
        }

        // if (++$count >= 20) break;
        echo "\n";
    }
} catch (PDOException $e) {
    // Trata erros de conexão ou execução
    echo "Erro: " . $e->getMessage();
}

// Encerra a conexão
$pdo = null;