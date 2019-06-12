<?php

include('AsyncdForMEC/System/Library/ParsePrintData.php');
// $uuid = '1f0043000c51363239393738';
$uuid = '360035000c51363239393738';
$ParsePrintData = new ParsePrintData($uuid);
var_dump('start：', microtime());
$print_data = $ParsePrintData->DbPdo->getAll("select * from print_org where uuid = '{$uuid}'");
$uuidarr = [
    "cfcd208495d565ef66e7dff9f98764da","c4ca4238a0b923820dcc509a6f75849b","c81e728d9d4c2f636f067f89cc14862c","eccbc87e4b5ce2fe28308fd9f2a7baf3",
    "a87ff679a2f3e71d9181a67b7542122c","e4da3b7fbbce2345d7772b0674a318d5","1679091c5a880faf6fb5e6087eb1b2dc","8f14e45fceea167a5a36dedd4bea2543",
    "c9f0f895fb98ab9159f51fd0297e236d","45c48cce2e2d7fbdea1afc51c7c6ad26","d3d9446802a44259755d38e6d163e820","6512bd43d9caa6e02c990b0a82652dca",
    "c20ad4d76fe97759aa27a0c99bff6710","c51ce410c124a10e0db5e4b97fc2af39","aab3238922bcc25a6f606eb525ffdc56","9bf31c7ff062936a96d3c8bd1f8f2ff3",
    "c74d97b01eae257e44aa9d5bade97baf","70efdf2ec9b086079795c442636b55fb","6f4922f45568161a8cdf4ad2299f6d23","1f0e3dad99908345f7439f8ffabdffc4",
    "98f13708210194c475687be6106a3b84","3c59dc048e8850243be8079a5c74d079","b6d767d2f8ed5d21a44b0e5886680cb9","37693cfc748049e45d87b8c7d8b9aacd",
    "1ff1de774005f8da13f42943881c655f","8e296a067a37563370ded05f5a3bf3ec","4e732ced3463d06de0ca9a15b6153677","02e74f10e0327ad868d138f2b4fdd6f0",
    "33e75ff09dd601bbe69f351039152189","6ea9ab1baa0efb9e19094440c317e21b","34173cb38f07f89ddbebc2ac9128303f","c16a5320fa475530d9583c34fd356ef5",
    "6364d3f0f495b6ab9dcf8d3b5c6e0b01","182be0c5cdcd5072bb1864cdee4d3d6e","e369853df766fa44e1ed0ff613f563bd","1c383cd30b7c298ab50293adfecb7b18",
    "19ca14e7ea6328a42e0eb13d585e4c22","a5bfc9e07964f8dddeb95fc584cd965d","a5771bce93e200c36f7cd9dfd0e5deaa","d67d8ab4f4c10bf22aa353e27879133c",
    // "d645920e395fedad7bbbed0eca3fe2e0","3416a75f4cea9109507cacd8e2f2aefc","a1d0c6e83f027327d8461063f4ac58a6","17e62166fc8586dfa4d1bc0e1742c08b",
    // "f7177163c833dff4b38fc8d2872f1ec6","6c8349cc7260ae62e3b1396831a8398f","d9d4f495e875a2e075a1a4a6e1b9770f","67c6a1e7ce56d3d6fa748ab6d9af3fd7",
    // "642e92efb79421734881b53e1e1b18b6","f457c545a9ded88f18ecee47145a72c0","c0c7c76d30bd3dcaefc96f40275bdc0a","2838023a778dfaecdc212708f721b788",
    // "9a1158154dfa42caddbd0694a4e9bdc8","d82c8d1619ad8176d665453cfb2e55f0","a684eceee76fc522773286a895bc8436","b53b3a3d6ab90ce0268229151c9bde11",
    // "9f61408e3afb633e50cdf1b20de6f466","72b32a1f754ba1c09b3695e0cb6cde7f","66f041e16a60928b05a7e228a89c3799","093f65e080a295f8076b1c5722a46aa2",
    // "072b030ba126b2f4b2374f342be9ed44","7f39f8317fbdb1988ef4c628eba02591","44f683a84163b3523afe57c2e008bc8c","03afdbd66e7929b125f8597834fa83a4",
    // "ea5d2f1c4608232e07d3aa3d998e5135","fc490ca45c00b1249bbe3554a4fdf6fb","3295c76acbf4caaed33c36b1b5fc2cb1","735b90b4568125ed6c3f678819b6e058",
    // "a3f390d88e4c41f2747bfa2f1b5f87db","14bfa6bb14875e45bba028a21ed38046","7cbbc409ec990f19c78c75bd1e06f215","e2c420d928d4bf8ce0ff2ec19b371514",
    // "32bb90e8976aab5298d5da10fe66f21d","d2ddea18f00665ce8623e36bd4e3c7c5","ad61ab143223efbc24c7d2583be69251","d09bf41544a3365a46c9077ebb5e35c3",
    // "fbd7939d674997cdb4692d34de8633c4","28dd2c7955ce926456240b2ff0100bde","35f4a8d465e6e1edc05f3d8ab658c551","d1fe173d08e959397adf34b1d77e88d7",
    // "f033ab37c30201f73f142449d037028d","43ec517d68b6edd3015b3edc9a11367b","9778d5d219c5080b9a6a17bef029331c","fe9fc289c3ff0af142b6d3bead98a923",
    // "68d30a9594728bc39aa24be94b319d21","3ef815416f775098fe977004015c6193","93db85ed909c13838ff95ccfa94cebd9","c7e1249ffc03eb9ded908c236bd1996d",
    // "2a38a4a9316c49e5a833517c45d31070","7647966b7343c29048673252e490f736","8613985ec49eb8f757ae6439e879bb2a","54229abfcfa5649e7003b83dd4755294",
    // "92cc227532d17e56e07902b254dfad10","98dce83da57b0395e163467c9dae521b","f4b9ec30ad9f68f89b29639786cb62ef","812b4ba287f5ee0bc9d43bbf5bbe87fb",
    // "26657d5ff9020d2abefe558796b99584","e2ef524fbf3d9fe611d5a8e90fefdc9c","ed3d2c21991e3bef5e069713af9fa6ca","ac627ab1ccbdb62ec96e702f07f6425b"
    ];
foreach($uuidarr as $uv){
    foreach($print_data as $v){
        $data = json_decode($v['data'],true);
        $tmpctime = explode(' ',$data['ctime']);
        $tmpctime[0] = substr($tmpctime[0],2);
        $ctime = $tmpctime[1].'.'.$tmpctime[0];
        $ParsePrintData->redis->addHashval('printdata_'.$uv, 'P'.$ctime, $data);
        //$ParsePrintData->redis->addHashval('printuuid', $uuid, floatval($ctime));
    }
}

var_dump('end：', microtime());exit;
//$ParsePrintData = new ParsePrintData($data['uuid']);
$result = $ParsePrintData->upDataToQrcode();