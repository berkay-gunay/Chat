<?php


include($_SERVER['DOCUMENT_ROOT'] . '/config.php'); ?>
<?php
$aktif_baslik = 'moduller';
$aktif_sayfa = 'onrequest';


if (isset($_POST['ajax']) && $_POST['ajax'] === "open_chat" && isset($_POST['receiver_id'], $_POST['user_id'])) {
  $receiver_id = $_POST['receiver_id'];
  $user_id = $_POST['user_id'];

  //Mesajın iletileceği kişinin adını alıyoruz
  $sql6 = "SELECT kullanici_adi FROM nis_users WHERE id = ?";
  $stmt7 = $baglanti->prepare($sql6);
  $stmt7->bind_param("i", $receiver_id);
  $stmt7->execute();
  $stmt7->bind_result($receiver);
  $stmt7->fetch();
  $stmt7->close();

  $sql2 = "SELECT * FROM nis_messages_users WHERE (sender=? AND receiver=?) OR (receiver=? AND sender=?)";
  $stmt = $baglanti->prepare($sql2);
  $stmt->bind_param("iiii", $user_id, $receiver_id, $user_id, $receiver_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    echo "<div id='subject' name='subject' class='p-4 border-b text-lg font-semibold' readonly required>" . htmlspecialchars($receiver) . "</div>";

    while ($message = $result->fetch_assoc()) {

      if ($message['sender'] != $receiver_id) {
        echo "<div class='flex justify-end mt-1'>
            <div class='bg-blue-500 text-white p-3 rounded-lg max-w-xs'>
                <p>" . htmlspecialchars($message['message']) . "</p>
                <p class='text-xs text-gray-200 mt-1 text-right'>" . htmlspecialchars(date("d F Y H:i", strtotime($message['created_at']))) . "</p>
            </div>
        </div>";
      } else {
        echo "<div class='flex justify-start mt-1'>
            <div class='bg-gray-200 p-3 rounded-lg max-w-xs'>
                <p>" . htmlspecialchars($message['message']) . "</p>
                <p class='text-xs text-gray-400 mt-1'>" . htmlspecialchars(date("d F Y H:i", strtotime($message['created_at']))) . "</p>
            </div>
        </div>";
      }
    }
  } else {
    echo "<div id='subject' name='subject' class='p-4 border-b text-lg font-semibold' readonly required>" . htmlspecialchars($receiver) . "</div>";
    echo "sohbet başlatın";
  }


  exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === "send_message" && isset($_POST['new_message'], $_POST['receiver_id'], $_POST['user_id'])) {

  $new_message = $_POST['new_message'];
  $receiver_id = $_POST['receiver_id'];
  $user_id = $_POST['user_id'];

  $sqlInsert = "INSERT INTO nis_messages_users(sender,receiver,message) VALUES (?,?,?)";
  $stmtInsert = $baglanti->prepare($sqlInsert);
  $stmtInsert->bind_param("iis", $user_id, $receiver_id, $new_message);
  $stmtInsert->execute();
  $stmtInsert->close();

  exit;
}


include($_SERVER['DOCUMENT_ROOT'] . '/admin/menu.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>On Request</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
    }

    /* Scrollbar gizleme */
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }

    .no-scrollbar {
      -ms-overflow-style: none;
      /* Internet Explorer 10+ */
      scrollbar-width: none;
      /* Firefox */
    }
  </style>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><i class="fa-solid fa-gauge"></i> Sohbet Paneli</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="">Ana Sayfa</a></li>
              <li class="breadcrumb-item active">Sohbet Paneli</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <section class="content">
      <div class="row">
        <div class="col-md-12">
          <div class="card card-outline card-info">
            <div class="card-header">
              <h3 class="card-title"> Gelen Mesajlar</h3>
            </div>
            <div class="card-body">

              <div class="bg-gray-100">
                <div class="flex h-[600px]">
                  <!-- Sol Taraf: Mesaj Listesi -->
                  <div class="w-1/5 bg-white shadow-md p-4 overflow-y-auto no-scrollbar">
                    <h2 class="text-lg font-semibold mb-4">Gelen Mesajlar</h2>
                    <div class="space-y-3">
                      <input type="hidden" id="user_id" value="<?= $user_id ?>">
                      <?php

                      $sqlUsers = "SELECT id,kullanici_adi FROM nis_users WHERE id != ?";
                      $stmtUsers = $baglanti->prepare($sqlUsers);
                      $stmtUsers->bind_param("i", $user_id);
                      $stmtUsers->execute();
                      $resultUsers = $stmtUsers->get_result();
                      if ($resultUsers->num_rows > 0) {

                        // Bu while ile başlatılan konuşmaları sol tarafta sıralıyoruz.
                        while ($rowUsers = $resultUsers->fetch_assoc()) {


                      ?>
                          <!-- <form method="POST" action="onrequest.php"> -->
                          <!--POST conversaiton_id yi gönderdik.-->

                          <button class="open_conversation w-full text-left p-3 bg-gray-200 rounded-lg cursor-pointer hover:bg-gray-300 flex items-center justify-between"
                            data-id="<?= $rowUsers['id'] ?>">


                            <div>
                              <p class="font-bold"><?php echo htmlspecialchars($rowUsers['kullanici_adi']); ?></p>

                            </div>
                            <div class="flex flex-col items-end">

                              <!-- <?php //if ($row['sender'] == "Admin") { 
                                    ?>
                                <span class="w-3 h-3 bg-red-500 rounded-full mt-4"></span>
                              <?php  //} else { 
                              ?>
                                <span class="w-3 h-3 bg-green-500 rounded-full mt-4"></span>
                              <?php //} 
                              ?> -->
                            </div>
                          </button>
                          <!-- </form> -->
                      <?php }
                      } ?>
                    </div>
                  </div>

                  <!-- Sağ Taraf: Mesajlaşma Alanı -->
                  <div class="w-4/5 flex flex-col bg-white shadow-md">
                    <div class="flex-1 p-4 overflow-y-auto space-y-4 no-scrollbar">
                      <div id="sag_taraf">

                      </div>
                    </div>
                    <!-- Mesaj Gönderme alanı -->
                    <div class="p-4 border-t flex items-center">

                      <textarea class="flex-1 p-2 border rounded-lg" id="newMessage" required placeholder="Mesajınızı yazın..."></textarea>
                      <button id="send_message" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Gönder</button>
                    </div>
                  </div>

                </div>
              </div>
            </div>
            <div class="card-footer">
              www.nisroc.net
            </div>

          </div>

        </div>
      </div>
  </div>
  </section>
  </div>
  <script>
    $(document).ready(function() {
      let receiver_id = null;
      const user_id = $('#user_id').val();
      let messageInterval = null;

      function getMessages() {
        if (receiver_id !== null) {
          $.post('', {
            ajax: 'open_chat',
            receiver_id: receiver_id,
            user_id: user_id
          }, function(data) {
            $('#sag_taraf').html(data);
          });
        }
      }

      $('.open_conversation').click(function() {
        receiver_id = $(this).data('id');
        getMessages();

        // Önceki interval’ı durdur
        if (messageInterval) {
          clearInterval(messageInterval);
        }

        // Yeni interval başlat
        messageInterval = setInterval(getMessages, 3000);
      });

      $('#send_message').click(function() {
        const new_message = $('#newMessage').val();
        if (new_message.trim() === "") return;

        $.post('', {
          ajax: 'send_message',
          receiver_id: receiver_id,
          user_id: user_id,
          new_message: new_message
        }, function(data) {
          $('#newMessage').val('');
          getMessages(); // Mesajı anında göster
        });
      });
    });
  </script>

</body>

</html>
<footer class="main-footer">
  <strong>Telif hakkı &copy; 2014-2023 <a href="https://mansurbilisim.com">Mansur Bilişim Ltd. Şti.</a></strong>
  Her hakkı saklıdır.
  <div class="float-right d-none d-sm-inline-block">
    <b>Version</b> 1.0.1
  </div>
</footer>