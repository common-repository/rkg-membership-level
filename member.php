<?php

global $wpdb, $table_prefix;
$table_name = $table_prefix . 'users';
if (isset($_POST["delete"])) {
  $delete_id = $_POST["delete"];
  $delete_id = $wpdb->prepare('%d', $delete_id);
  $wpdb->delete(
    $table_name,
    array(
      'ID' => $delete_id
    )
  );
}

$results = $wpdb->get_results("SELECT * FROM $table_name"); // Query to fetch data from database table and storing in $results
if (!empty($results))                        // Checking if $results have some values or not
{    ?>
  <div class="container mt-3">
    <h2>Members List</h2>

<form action="" method="post">

    <table class="table table-hover">

      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Email</th>
          <th>Membership_level</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($results as $row) {
        ?>
          <tr>

            <td><?php echo esc_html_e( $row->ID ) ?></td>
            <td><?php echo esc_html_e( $row->user_login) ?></td>
            <td><?php echo esc_html_e( $row->user_email )?></td>
            <td><?php echo esc_html_e( $row->user_membership_level )?></td>
            <td><button name="delete" value="<?php echo esc_html_e($row->ID); ?>">Delete</button></td>
           
          <?php
        }

          ?>
          </tr>

      </tbody>
    </table>
    </form>

  </div>
<?php
} ?>
