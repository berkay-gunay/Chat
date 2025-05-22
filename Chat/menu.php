<?php

//Chat Bildirim
if (isset($_POST['ajax']) && $_POST['ajax'] === 'get_notifications') {
  $countNotification = 0;
  $val1 = 1;
  $val0 = 0;
  $dropdownItems = ""; // dropdown içeriklerini biriktireceğiz

  $sqlOnlineUsers = "SELECT * FROM nis_users WHERE is_online = ?";
  $stmtOnlineUsers = $baglanti->prepare($sqlOnlineUsers);
  $stmtOnlineUsers->bind_param("i", $val1);
  $stmtOnlineUsers->execute();
  $resultOnlineUsers = $stmtOnlineUsers->get_result();

  if ($resultOnlineUsers->num_rows > 0) {
    while ($rowOnlineUsers = $resultOnlineUsers->fetch_assoc()) {

      $sqlNotification = "SELECT * FROM nis_messages_users 
                        WHERE ((sender=? AND receiver=?) OR (receiver=? AND sender=?)) AND is_read=? 
                        ORDER BY created_at DESC LIMIT 1";
      $stmtNotification = $baglanti->prepare($sqlNotification);
      $stmtNotification->bind_param("iiiii", $user_id, $rowOnlineUsers['id'], $user_id, $rowOnlineUsers['id'], $val0);
      $stmtNotification->execute();
      $resultNotification = $stmtNotification->get_result();

      if ($resultNotification->num_rows > 0) {
        $rowNotification = $resultNotification->fetch_assoc();
        if ($rowNotification['sender'] != $user_id) {
          $countNotification++;

          //Gelen bildirimleri birleştiriyoruz
          $dropdownItems .= '<a href="/admin/contact.php?receiver_id=' . htmlspecialchars($rowOnlineUsers['id']) . '" class="dropdown-item notification-link" data-receiver-id="' . htmlspecialchars($rowOnlineUsers['id']) . '">
                                  <div class="media">
                                    <img src="/default-icon.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                                    <div class="media-body">
                                      <h3 class="dropdown-item-title">' . htmlspecialchars($rowOnlineUsers['kullanici_adi']) . '
                                      <span class="float-right text-sm text-danger notification-dismiss" data-receiver-id="' . htmlspecialchars($rowOnlineUsers['id']) . '">
                                          <i class="fa-solid fa-xmark"></i>
                                        </span> </h3>
                                      <p class="text-sm">' . mb_substr(htmlspecialchars($rowNotification['message']), 0, 20, 'UTF-8') . '</p>
                                      <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> ' . htmlspecialchars($rowNotification['created_at']) . '</p>
                                    </div>
                                  </div>
                                </a>
                                <div class="dropdown-divider"></div>';
        }
      }
    }

    // Şimdi dropdown'ı bas
    if ($countNotification > 0) {
      echo '<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">';
      echo $dropdownItems;
      echo '<div class="dropdown-divider"></div>
          <a href="/admin/contact.php" class="dropdown-item dropdown-footer">See All Messages</a>
             </div>';
      echo '<a href="#" class="nav-link" data-toggle="dropdown"><i class="fa-solid fa-comments"></i> Sohbet <span class="badge badge-danger navbar-badge">' . $countNotification . '</span></a>';
    } else {
      echo '<a href="/admin/contact.php" class="nav-link"><i class="fa-solid fa-comments"></i> Sohbet </a>';
    }
  } else {
    echo '<a href="/admin/contact.php" class="nav-link"><i class="fa-solid fa-comments"></i> Sohbet </a>';
  }

  exit;
}
//Bildirime tıklanınca bildirimleri kaldır
if (isset($_POST['ajax']) && $_POST['ajax'] === 'mark_notification_read_single' && isset($_POST['receiver_id'])) {
  $val0 = 0;
  $receiver_id = $_POST['receiver_id'];
  // Son gelen mesajlardan, kullanıcının alıcı olduğu ve okunmamış olanları işaretle
  $sqlMarkRead = "UPDATE nis_messages_users 
                  SET is_read = 1 
                  WHERE sender = ? AND receiver = ? AND is_read = ?";
  $stmtMarkRead = $baglanti->prepare($sqlMarkRead);
  $stmtMarkRead->bind_param("iii", $receiver_id, $user_id, $val0);
  $stmtMarkRead->execute();
  $stmtMarkRead->close();
  exit;
}

?>


<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
</head>

<body class="hold-transition sidebar-mini layout-fixed">

  
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    
    <ul class="navbar-nav">

      <li class="nav-item dropdown d-none d-sm-inline-block">

        <div id="show_notifications">
          <a href="#" class="nav-link"><i class="fa-solid fa-comments"></i> Sohbet </a>
        </div>

      </li>

    </ul>
  </nav>
  


  <!-- jQuery -->
  <script src="/content/plugins/jquery/jquery.min.js"></script>
  

</body>

</html>



<script>

  $(document).ready(function() {


    //Bildirimleri göstermek için
    function get_notifications() {
      // get_notifications içinde, sadece rozet güncellemek istiyorsan:
      const currentDropdownOpen = $('#show_notifications .dropdown-menu').hasClass('show');
      $.post('', {
        ajax: 'get_notifications'
      }, function(data) {
        $('#show_notifications').html(data);
        if (currentDropdownOpen) {
          $('#show_notifications .dropdown-menu').addClass('show'); // yeniden açık tut
        }
      });

    }

    get_notifications();
    setInterval(get_notifications, 5000);

    //Bildirimdeki herhangi bir kişiye tıklanırsa
    $(document).on('click', '.notification-link', function(e) {
      const receiverId = $(this).data('receiver-id');
      $.post('', {
        ajax: 'mark_notification_read_single',
        receiver_id: receiverId
      });
    });

    //Her bildirimde bulunan x butonu
    $(document).on('click', '.notification-dismiss', function(e) {
      e.stopPropagation(); // <a>’ya tıklamayı engelle
      e.preventDefault(); // sayfa yönlendirmesini durdur

      const receiverId = $(this).data('receiver-id');

      // Bildirimi okundu yap
      $.post('', {
        ajax: 'mark_notification_read_single',
        receiver_id: receiverId
      }, function() {
        // Bildirimi DOM'dan kaldır
        const parentLink = $(e.target).closest('a.notification-link'); // Bildirime ait a etiketine eriştik
        const divider = parentLink.next('.dropdown-divider'); // Bildirimin altındaki divider a eriştik 
        parentLink.remove(); //İkisini de kaldırıyoruz
        divider.remove();

        get_notifications();
      });
    });
  })
</script>