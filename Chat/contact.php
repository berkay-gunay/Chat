<?php

include($_SERVER['DOCUMENT_ROOT'] . '/config.php');
date_default_timezone_set("Europe/Istanbul");
$aktif_baslik = 'moduller';
$aktif_sayfa = 'onrequest';

if (isset($_POST['ajax']) && $_POST['ajax'] === "get_left_side" && isset($_POST['user_id'])) {

  $user_id = $_POST['user_id'];

  $sqlUsers = "SELECT id, kullanici_adi, is_online, last_activity 
               FROM users 
               WHERE id != ? 
               ORDER BY is_online DESC, last_activity DESC";
  $stmtUsers = $baglanti->prepare($sqlUsers);
  $stmtUsers->bind_param("i", $user_id);
  $stmtUsers->execute();
  $resultUsers = $stmtUsers->get_result();
  if ($resultUsers->num_rows > 0) {

    // Bu while ile başlatılan konuşmaları sol tarafta sıralıyoruz.
    while ($rowUsers = $resultUsers->fetch_assoc()) {


      echo '<button class="open_conversation w-full text-left p-3 bg-gray-200 rounded-lg cursor-pointer hover:bg-gray-300 flex items-center justify-between"
                data-id="' . htmlspecialchars($rowUsers["id"]) . '">

                <img src="/default-icon.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">

        <div>
          <p class="font-bold">' . htmlspecialchars($rowUsers['kullanici_adi']) . '</p>
        </div>
                            
      <div class="flex flex-col items-end">';
      if ($rowUsers['is_online'] === 0) {
        echo '<span class="w-3 h-3 bg-red-500 rounded-full mt-2"></span>';
      } else {
        echo '<span class="w-3 h-3 bg-green-500 rounded-full mt-2"></span>';
      }
      echo '</div>';

      echo '</button>';
    }
  }

  exit;
}
if (isset($_POST['ajax']) && $_POST['ajax'] === "get_textarea_button" && isset($_POST['receiver_id'])) {
  $receiver_id = $_POST['receiver_id'];


  //Mesajın iletileceği kişinin adını alıyoruz
  $sql8 = "SELECT is_online FROM users WHERE id = ?";
  $stmt8 = $baglanti->prepare($sql8);
  $stmt8->bind_param("i", $receiver_id);
  $stmt8->execute();
  $stmt8->bind_result($is_online);
  $stmt8->fetch();
  $stmt8->close();

  if ($is_online === 1) {
    $attribute = "";
    $attribute_button = "";
    $placeholder = "Mesajınızı yazın...";
  } else {
    $attribute = "readonly";
    $attribute_button = "disabled";
    $placeholder = "Çevrimdışı kullanıcıya Mesaj gönderilemez";
  }


  echo '<div class="flex flex-col w-full">
  <!-- Önizleme buraya -->
  <div id="filePreview" class="text-sm text-gray-600 mb-2"></div>

  <div class="flex items-center">
    <!-- Dosya seçme ikonu -->
    <label for="message_file" class="cursor-pointer ml-2">
      <i class="fa-solid fa-paperclip text-lg mr-4"></i>
    </label>
    <input type="file" id="message_file" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">

    <!-- Mesaj alanı -->
    <textarea class="flex-1 p-2 border rounded-lg" id="newMessage"' . $attribute . ' placeholder="' . $placeholder . '"></textarea>

    <!-- Gönder butonu -->
    <button id="send_message" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600" ' . $attribute_button . ' >Gönder</button>
  </div>
</div>
';


  exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === "open_chat" && isset($_POST['receiver_id'], $_POST['user_id'])) {
  $receiver_id = $_POST['receiver_id'];
  $user_id = $_POST['user_id'];
  $val0 = 0;

  //Mesajın iletileceği kişinin adını alıyoruz
  $sql6 = "SELECT kullanici_adi FROM users WHERE id = ?";
  $stmt7 = $baglanti->prepare($sql6);
  $stmt7->bind_param("i", $receiver_id);
  $stmt7->execute();
  $stmt7->bind_result($receiver);
  $stmt7->fetch();
  $stmt7->close();

  // Son gelen mesajlardan, kullanıcının alıcı olduğu ve okunmamış olanları işaretle
  $sqlMarkRead = "UPDATE messages_users 
                  SET is_read = 1 
                  WHERE sender = ? AND receiver = ? AND is_read = ?";
  $stmtMarkRead = $baglanti->prepare($sqlMarkRead);
  $stmtMarkRead->bind_param("iii", $receiver_id, $user_id, $val0);
  $stmtMarkRead->execute();
  $stmtMarkRead->close();


  $sql2 = "SELECT * FROM messages_users WHERE (sender=? AND receiver=?) OR (receiver=? AND sender=?)";
  $stmt = $baglanti->prepare($sql2);
  $stmt->bind_param("iiii", $user_id, $receiver_id, $user_id, $receiver_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    echo "<div id='subject' name='subject' class='sticky top-0 bg-white p-4 border-b text-lg font-semibold' readonly required>" . htmlspecialchars($receiver) . "</div>";

    echo '<div id="messageArea">';

    while ($message = $result->fetch_assoc()) {
      // Mesajın içeriği
      if (!empty($message['file_name']) && !empty($message['message'])) {
        if (str_contains($message['file_name'], ".png") || str_contains($message['file_name'], ".jpeg") || str_contains($message['file_name'], ".jpg")) {
          $file_message = "<img class='chat-image' src='/admin/content/chatupload/" . htmlspecialchars($message['file_name']) . "' style='width:350px; cursor:pointer;'><br>" . $message['message'];
        } else {
          $file_message = "<a href='/admin/content/chatupload/" . htmlspecialchars($message['file_name']) . "' target='_blank''>" . htmlspecialchars($message['file_name']) . "</a><br>" . $message['message'];
        }
      } else if (empty($message['file_name']) && !empty($message['message'])) {
        $file_message = $message['message'];
      } else {
        if (str_contains($message['file_name'], ".png") || str_contains($message['file_name'], ".jpeg") || str_contains($message['file_name'], ".jpg")) {
          $file_message = "<img class='chat-image' src='/admin/content/chatupload/" . htmlspecialchars($message['file_name']) . "' style='width:350px; cursor:pointer;'>";
        } else {
          $file_message = "<a href='/admin/content/chatupload/" . htmlspecialchars($message['file_name']) . "' target='_blank''>" . htmlspecialchars($message['file_name']) . "</a>";
        }
      }

      // Butonların 1dk sonra yok olması
      $now = time(); // şu anki zaman (timestamp)
      $created_at = strtotime($message['created_at']);

      $timePassed = (int)floor(($now - $created_at) / 60);

      if ($timePassed < 1) {
        $buttons = "<button class='deleteMessage' data-id=" . $message['id'] . "><i class='fa-solid fa-xmark mr-1 text-sm hover:text-red-600'></i></button>
                    <button class='editMessage' data-id=" . $message['id'] . "><i class='fa-solid fa-pen text-xs hover:text-green-300'></i></button>";
      } else {
        $buttons = '';
      }



      if ($message['sender'] != $receiver_id) {
        echo "<div class='message-container flex justify-end mt-1 mr-2'>
                <div class='bg-blue-500 text-white p-3 rounded-lg inline-block max-w-[65%]'>
                  <div class='flex items-start max-w-full'>
                    <img src='/default-icon.jpg' alt='User Avatar' class='w-10 h-10 mr-3 rounded-full shrink-0'>
                    <div class='w-full'>
                      <p class='break-all whitespace-pre-wrap'>" . $file_message . "</p>
                      <p class='text-xs text-gray-200 mt-1 text-right'>" . htmlspecialchars(date("d F Y H:i", strtotime($message['created_at']))) . "</p>
                    </div>
                  </div>" .
          $buttons .
          "</div>
              </div>";
      } else {
        echo "<div class='flex justify-start mt-1 ml-2'>
                <div class='bg-gray-200 p-3 rounded-lg inline-block max-w-[65%]'>
                  <div class='flex items-start max-w-full'>
                    <img src='/default-icon.jpg' alt='User Avatar' class='w-10 h-10 mr-3 rounded-full shrink-0'>
                    <div class='w-full'>
                      <p class='break-all whitespace-pre-wrap'>" . $file_message . "</p>
                      <p class='text-xs text-gray-400 mt-1 text-right'>" . htmlspecialchars(date("d F Y H:i", strtotime($message['created_at']))) . "</p>
                    </div>
                  </div>
                </div>
              </div>";
      }
    }
    echo "</div>";
  } else {
    echo "<div id='subject' name='subject' class='p-4 border-b text-lg font-semibold' readonly required>" . htmlspecialchars($receiver) . "</div>";
    echo "sohbet başlatın";
  }


  exit;
}



if (isset($_POST['ajax']) && $_POST['ajax'] === "send_message") {

  $message = htmlspecialchars(trim($_POST['new_message']), ENT_QUOTES, 'UTF-8');
  $receiver_id = (int)$_POST['receiver_id'];
  $user_id = (int)$_POST['user_id'];


  $fileName = null;
  $filePath = null;
  $fileType = null;

  //Dosya gönderilmişse işle
  if (!empty($_FILES['attachment']['name'])) {

    //Güvenli dosya türü kontrolü (.exe, .php, .bat gibi tehlikeli dosyaların yüklenmesini engeller)
    $allowedTypes = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'image/jpeg',
      'image/png'
    ];

    $fileMime = mime_content_type($_FILES['attachment']['tmp_name']);

    if (in_array($fileMime, $allowedTypes)) {
      $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/admin/content/chatupload/';
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

      $originalName = basename($_FILES['attachment']['name']);
      $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\\-_]/', '_', $originalName);
      $filePath = $uploadDir . $fileName;

      if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
        $fileType = $fileMime;
      } else {
        $fileName = null; // Hatalıysa boş bırak
      }
    }
  }

  // 4. Mesajı ve dosyayı veritabanına kaydet
  $stmt = $baglanti->prepare("INSERT INTO messages_users (sender, receiver, message, file_name, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("iissss", $user_id, $receiver_id, $message, $fileName, $fileName, $fileType);
  $stmt->execute();
  $stmt->close();

  exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === "delete_message" && isset($_POST['record_id'])) {

  $record_id = intval($_POST['record_id']);

  $sql = "DELETE FROM messages_users WHERE id=?";
  $stmt = $baglanti->prepare($sql);
  $stmt->bind_param("i", $record_id);
  $stmt->execute();
  $stmt->close();

  exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === "edit_message" && isset($_POST['record_id'], $_POST['new_message'])) {

  $record_id = $_POST['record_id'];
  $new_message = $_POST['new_message'];
  $sql = "UPDATE messages_users SET message = ? WHERE id = ?";
  $stmt = $baglanti->prepare($sql);
  $stmt->bind_param('si', $new_message, $record_id);
  $stmt->execute();
  $stmt->close();

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

    .word-wrap-break {
      word-wrap: break-word;
      overflow-wrap: break-word;
      white-space: normal;
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
                    <input type="hidden" id="user_id" value="<?= $user_id ?>">
                    <?php
                    $receiver_id_from_get = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 'null';
                    ?>
                    <input type="hidden" id="receiver_id_from_get" value="<?= $receiver_id_from_get ?>">
                    <div class=" sol-taraf space-y-3">

                    </div>

                  </div>

                  <!-- Sağ Taraf: Mesajlaşma Alanı -->
                  <div class="w-4/5 flex flex-col bg-white shadow-md">
                    <div id="sag_taraf" class="flex-1 pt-0 pb-4 overflow-y-auto space-y-4 no-scrollbar">

                    </div>

                    <!-- Mesaj Gönderme alanı -->
                    <div id="sendMessageArea" class="p-4 border-t flex items-center">


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

  <!-- Modal kutusu: Başta gizli, sadece resme tıklanırsa görünür -->
  <div id="imageModal"
    class="hidden fixed inset-0 bg-black bg-opacity-80 z-[9999] flex justify-center items-center">
    <span id="closeModal"
      class="absolute top-5 right-8 text-white text-3xl cursor-pointer hover:text-red-400">&times;</span>
    <img id="modalImage"
      src=""
      class="max-w-[90%] max-h-[90%] rounded shadow-lg border-4 border-white"
      alt="Görüntü">
  </div>

  <script>
    $(document).ready(function() {
      let receiver_id = null;
      const user_id = $('#user_id').val();
      let messageInterval = null;
      //let scrollCheck = true;
      let editMessage = 0;
      let editMessageId = 0;

      // Mesajları getiriyoruz
      function getMessages(forceScroll = false) {
        if (receiver_id !== null) {
          $.post('', {
            ajax: 'open_chat',
            receiver_id: receiver_id,
            user_id: user_id
          }, function(data) {
            $('#sag_taraf').html(data);

            if (forceScroll) {
              scrollToBottom(); // zorla kaydır
            } else {
              scrollToBottomIfNeeded(); // sadece gerekiyorsa kaydır
            }
          });
        }
      }

      //Sol Taraf
      function getLeftSide() {

        $.post('', {
          ajax: 'get_left_side',
          user_id: user_id
        }, function(data) {
          $('.sol-taraf').html(data);

        });

      }

      function getTextareaButton() {
        if (receiver_id !== null) {
          $.post('', {
            ajax: 'get_textarea_button',
            receiver_id: receiver_id
          }, function(data) {
            $('#sendMessageArea').html(data);

          });
        }
      }

      getLeftSide();
      setInterval(getLeftSide, 5000);

      //Sohbet alanı açıldığında
      $(document).on('click', '.open_conversation', function() {
        receiver_id = $(this).data('id');
        setTimeout(function() {
          getMessages(true);
          getTextareaButton();
        }, 4);


        // Önceki interval’ı durdur
        if (messageInterval) {
          clearInterval(messageInterval);
        }

        // Yeni interval başlat
        messageInterval = setInterval(() => getMessages(false), 3000);

      });

      //Gönder butonu
      $(document).on('click', '#send_message', function() {


        //Mesaj düzelenmiyorsa
        if (editMessage == 0) {


          const newMessage = $('#newMessage').val().trim();
          const file = $('#message_file')[0].files[0]; // İlk dosyayı al
          //const receiver_id = window.receiver_id; // globalden al (chat açıldığında atanıyor)
          const user_id = $('#user_id').val(); // oturum açmış kullanıcı

          if (!newMessage && !file) return; // boş mesaj ve dosya yoksa gönderme

          //FormData, hem dosya hem metin verilerini aynı anda gönderebilen özel bir veri yapısıdır.
          const formData = new FormData();
          formData.append('ajax', 'send_message');
          formData.append('receiver_id', receiver_id);
          formData.append('user_id', user_id);
          formData.append('new_message', newMessage);

          if (file) {
            formData.append('attachment', file);
          }

          $.ajax({
            url: '', // aynı PHP sayfasına gönder
            type: 'POST',
            data: formData,
            contentType: false, // jQuery’ye "header ayarlarını elleme, FormData kendi ayarlayacak" diyoruz.
            processData: false, // jQuery’ye "veriyi string'e çevirmeyi deneme" diyoruz (çünkü bu bir dosya)
            success: function() {
              $('#newMessage').val('');
              $('#message_file').val('');
              $('#filePreview').text('');
              getMessages(true); // yeniden mesajları yükle
            },
            error: function() {
              alert('Mesaj gönderilemedi. Lütfen tekrar deneyin.');
            }
          });

        } else { //Mesaj düzenleniyorsa

          const newMessage = $('#newMessage').val().trim();

          $.post('', {
            ajax: 'edit_message',
            record_id: editMessageId,
            new_message: newMessage
          }, function(data) {
            editMessage = 0;
            editMessageId = 0;
            $('#newMessage').val('');
            getMessages(true);
          });
        }

      });

      //bildirimde tıklandıysa
      const receiver_id_initial = $('#receiver_id_from_get').val();
      if (receiver_id_initial !== "null") {
        receiver_id = receiver_id_initial;
        getMessages(true);


        // Arka arkaya çekmek için interval başlat
        messageInterval = setInterval(() => getMessages(false), 3000);
        getTextareaButton();
      }

      //ilk açılmadan sonraki kısım için(setInterval kısımları için)
      function scrollToBottomIfNeeded() {
        const $scrollDiv = $('#sag_taraf');
        const isNearBottom =
          $scrollDiv[0].scrollHeight - $scrollDiv.scrollTop() <= $scrollDiv[0].clientHeight + 140;

        if (isNearBottom) {
          $scrollDiv.scrollTop($scrollDiv[0].scrollHeight);
        }
      }

      //Mesajlar ilk açıldığında
      function scrollToBottom() {
        const $scrollDiv = $('#sag_taraf');
        $scrollDiv.scrollTop($scrollDiv[0].scrollHeight);
      }

      $(document).on('click', '.chat-image', function() {
        var imageUrl = $(this).attr('src');
        if (!imageUrl || imageUrl.trim() === "" || imageUrl === "undefined") return;
        if (!imageUrl.match(/\.(jpeg|jpg|png)$/i)) return;

        $('#modalImage').attr('src', imageUrl);
        $('#imageModal').removeClass('hidden').fadeIn(); // fadein-> animasyonla getiriyoruz (başta hidden olmasının sebebi sayfayı açınca boş çalışması)
      });

      $('#closeModal, #imageModal').on('click', function(e) {
        if (e.target.id === 'imageModal' || e.target.id === 'closeModal') {
          $('#imageModal').fadeOut(function() { // fadeout -> animasyona gizliyoruz
            $(this).addClass('hidden'); // tamamen kaybolunca gizle
          });
        }
      });

      // Modal dışına tıklayınca da kapansın
      $('#imageModal').click(function(e) {
        if (e.target.id === 'imageModal') {
          $(this).fadeOut();
        }
      });

      //Ek seçildiğinde
      $(document).on('change', '#message_file', function() {
        const file = this.files[0];
        const $preview = $('#filePreview');

        if (!file) {
          $preview.addClass('hidden').html('');
          return;
        }

        let fileHTML = '';
        const fileName = file.name;
        const fileExt = fileName.split('.').pop().toLowerCase();

        if (['jpg', 'jpeg', 'png'].includes(fileExt)) {

          const reader = new FileReader();
          reader.onload = function(e) {
            fileHTML = `
            <img src="${e.target.result}" class="w-12 h-12 object-cover rounded border" alt="önizleme">
            <span>${fileName}</span>
            <span id="removeFile" class="text-red-500 cursor-pointer ml-2 text-xl font-bold">&times;</span>
            `;
            $preview.html(fileHTML).removeClass('hidden');
          };
          reader.readAsDataURL(file);
        } else {
          let iconClass = 'fa-file';
          if (fileExt === 'pdf') iconClass = 'fa-file-word';
          else if (['doc', 'docx'].includes(fileExt)) iconClass = 'fa-file-word';
          else if (['xls', 'xlsx'].includes(fileExt)) iconClass = 'fa-file-excel';

          fileHTML = `
          
          <i class="fa-solid ${iconClass} text-xl text-gray-700"></i>
          <span>${fileName}</span>
          <span id = "removeFile" class="text-red-500 cursor-pointer ml-2 text-xl font-bold">&times;</span>
          
          `;
          $preview.html(fileHTML).removeClass('hidden');
        }
      });
      $(document).on('click', '#removeFile', function() {
        $('#filePreview').html('').addClass('hidden');

        // Dosya input’unu DOM’dan kaldırıp yeniden ekleyerek sıfırla (böylece seçilenden de kalırmış oluyoruz)
        const $newInput = $('<input type="file" id="message_file" class = "hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">');
        $('#message_file').remove(); // Eski inputu kaldır
        $('label[for="message_file"]').after($newInput); // Yeni inputu ekle
      });


      $(document).on('click', '.deleteMessage', function() {
        const record_id = $(this).data('id');

        $.post('', {
          ajax: 'delete_message',
          record_id: record_id
        }, function(data) {
          $('#messageDiv').remove();
          getMessages(true);
        });
      });

      $(document).on('click', '.editMessage', function() {
        editMessageId = $(this).data('id');
          editMessage = 1;

        const messageText = $(this).closest('.message-container').find('p').first().text();
        $('#newMessage').val(messageText).focus();
      });
    });
  </script>



</body>

</html>
<footer class="main-footer">
  <strong>Telif hakkı &copy; 2014-2025 <a href="https://mansurbilisim.com">Mansur Bilişim Ltd. Şti.</a></strong>
  Her hakkı saklıdır.
  <div class="float-right d-none d-sm-inline-block">
    <b>Version</b> 1.0.1
  </div>
</footer>