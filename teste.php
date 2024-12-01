<?php

$url = "https://www.metacritic.com/game/dark-souls-remastered/critic-reviews/?platform=nintendo-switch";

// Obter o HTML do website
$html = file_get_contents($url);

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
                    echo $metascore;
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