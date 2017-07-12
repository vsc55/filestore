<?php
  $dataurl = "ajax.php?module=filesystem&driver=FTP&command=getJSON&jdata=grid";
?>
<br/>
<div id="toolbar-ftpgrid">
  <a href='?display=filesystem&driver=FTP&view=form' class='btn btn-default'><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add FTP Instance")?></a>
</div>
<table id="ftpgrid"
    data-url="<?php echo $dataurl?>"
    data-cache="false"
    data-cookie="true"
    data-cookie-id-table="ftpgrid"
    data-toolbar="#toolbar-ftpgrid"
    data-maintain-selected="true"
    data-show-columns="true"
    data-show-toggle="true"
    data-toggle="table"
    data-pagination="true"
    data-search="true"
    class="table table-striped">
  <thead>
    <tr>
      <th data-field="name"><?php echo _("Item")?></th>
      <th data-field="description"><?php echo _("Description")?></th>
      <th data-field="id" data-formatter="linkFormatter"><?php echo _("Actions")?></th>
    </tr>
  </thead>
</table>
