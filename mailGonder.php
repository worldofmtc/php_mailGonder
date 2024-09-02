
<?php
require 'mail/PHPMailerAutoload.php';

class ContactManagement {


public function mailler($id,$kolon, $eksql,$orderby,$limit) {
    global $con;
    
    $sql = "SELECT ".$kolon." FROM gelen_mail WHERE 1=1";
    if (!empty($id)) {
        $sql .= " AND id = ".$id."";
    }
    if (!empty($eksql)) {
        $sql .= " " . $eksql;
    }

    if (!empty($limit)) {
        $limit = '  limit '.$limit;
    }

    if (!empty($orderby)) {
        $orderby = ' ORDER BY ' . $orderby;
    } else {
        // Varsayılan sıralama
        $orderby = ' ORDER BY id DESC';
    }
    $sql .= " " . $orderby.$limit;

//  echo $sql;
    $query = $con->prepare($sql);
    $query->execute();
    
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    
   
    if ($result) {
        return $result;
    } else {
        return "0";
    }
}


public function mail_okundu($id) {
    global $con;
    
    $sql = "UPDATE gelen_mail SET okundu = :okundu WHERE id = :id";
    
    // Sorguyu hazırla
    $query = $con->prepare($sql);
    
    // Parametreleri bağla
    $result= $query->execute(array(':okundu' => 1, ':id' => $id));

    if ($result) {
        return 1;
    } else {
        return 0;
    }
}




public function mailGonder($ayar, $adsoyad, $eposta, $kime,$konu, $mesaj, $addAdress, $dinamikIcerik) {

         if ($konu == '') {
             $konu = $ayar['baslik'];
         }

    try {
        $mail = new PHPMailer(true);
          
        // SMTP ayarları
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = $ayar['smtp_server'];
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = $ayar['smtp_ssl']; // 'tls' olabilir
        $mail->Username   = $ayar['smtp_username'];
        $mail->Password   = $ayar['smtp_password'];
        $mail->Port       = $ayar['smtp_port'];

        // Alıcı ve gönderici ayarları
        $mail->setFrom($eposta, $adsoyad);
        $mail->addAddress($kime);
        if (!empty($addAdress)) {
            $mail->addAddress($addAdress);
        }else {
            $mail->addAddress($ayar['smtp_username']);
        }
        $mail->addReplyTo($kime);

        $mail->CharSet = "utf-8";
        $mail->isHTML(true);

        // E-posta gövdesi HTML formatında oluştur
        $emailBody = "
            <html>
            <head>
                <style>
                    .header, .footer {
                        background-color: #2960a9;
                        color: white;
                        text-align: center;
                        padding: 10px;
                    }
                    .body {
                        font-family: Arial, sans-serif;
                        padding: 20px;
                    }
                    .body p {
                        margin: 5px 0;
                    }
                         .container {
                        max-width: 600px; /* Adjust this width as needed */
                        margin: 0 auto; /* Center the container */
                        padding: 20px; /* Optional: Add padding around the container */
                        border: 1px solid #ddd; /* Optional: Add a border for a clean look */
                        background-color: #f9f9f9; /* Optional: Background color for the container */
                    }
                </style>
            </head>
            <body  class='container'>";

        // Genel logo varsa başlık ekle
      
            $emailBody .= "
                <div class='header '>
                    <img src='https://twm.com.tr/cp/assets/images/logo/logo.png' alt='Genel Logo' style='height: 50px;'>
                </div>";
        

        $emailBody .= "
                <div class='body'>";

        // Dışarıdan alınan dinamik içeriği ekle
        $emailBody .= $dinamikIcerik;

        $emailBody .= "
                </div>
                <div class='footer' >
                    <a style='color: white;' href='".$ayar['link']."'>".$ayar['baslik']."</a>
                </div>
            </body>
            </html>
        ";

        $mail->Body = $emailBody;
        $mail->Subject  = $konu;

        // E-posta gönder
        $mail->send();

        $basari = 1;
        // E-posta gönderim kaydını veritabanına ekle

       $this-> insertGelenMail($adsoyad, $eposta, $konu, $mesaj, $kime, $basari);
        

        return $basari;
    } catch (Exception $e) {
        $basari = 0;
        // Hatalı e-posta gönderim kaydını veritabanına ekle
        $this-> insertGelenMail($adsoyad, $eposta, $konu, $mesaj, $kime, $basari);

        echo "Mesaj gönderilemedi. Hata: {$e->getMessage()}";
        return $basari;
    }
}

        public function insertGelenMail($adsoyad, $eposta, $konu, $mesaj, $kime, $basari) {
            global $con;
            $tarih = date("Y-m-d H:i:s"); // Geçerli tarih ve saat bilgisini alır
            
            $sql = "INSERT INTO gelen_mail (adsoyad, eposta, konu, mesaj, kime, tarih, basari, okundu) 
                    VALUES (:adsoyad, :eposta, :konu, :mesaj, :kime, :tarih, :basari, 0)";
                    
            $query = $con->prepare($sql);
            
            $result = $query->execute(array(
                ":adsoyad" => $adsoyad,
                ":eposta" => $eposta,
                ":konu" => $konu,
                ":mesaj" => $mesaj,
                ":kime" => $kime,
                ":tarih" => $tarih,
                ":basari" => $basari
            ));
        
            if ($result) {
                return "1";
            } else {
                return "0";
            }
        }


}

?>