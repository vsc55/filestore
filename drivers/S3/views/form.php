<?php
$disabled = (isset($readonly) && !empty($readonly))?' disabled ':'';
if (!isset($id)) {
	$id = "";
}
$fstype = isset($fstype)?$fstype:'auto';
?>
<div class="container-fluid">
	<h1><?php echo _('AWSS3')?></h1>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div class="display full-border">
            <form class="fpbx-submit" action="" method="post" id="server_form" name="server_form">
            	<input type="hidden" name="action" value="save">
            	<input type="hidden" name="id" value="<?php echo isset($_GET['id'])?$_GET['id']:''?>">
            	<input type="hidden" name="driver" value="S3">
            	<!--Bucket Name-->
            	<div class="element-container">
            		<div class="row">
            			<div class="form-group">
            				<div class="col-md-3">
            					<label class="control-label" for="bucket"><?php echo _("Bucket Name") ?></label>
            				</div>
            				<div class="col-md-9">
            					<input type="text" class="form-control" id="bucket" name="bucket" value="<?php echo isset($bucket)?$bucket:''?>"<?php echo $disabled?>>
            				</div>
            			</div>
            		</div>
            	</div>
            	<!--Description-->
            	<div class="element-container">
            		<div class="row">
            			<div class="form-group">
            				<div class="col-md-3">
            					<label class="control-label" for="desc"><?php echo _("Description") ?></label>
            				</div>
            				<div class="col-md-9">
            					<input type="text" class="form-control" id="desc" name="desc" value="<?php echo isset($desc)?$desc:''?>"<?php echo $disabled?>>
            				</div>
            			</div>
            		</div>
            	</div>
            	<!--END Description-->
            	<!--AWS Access Key-->
            	<div class="element-container">
            		<div class="row">
            			<div class="form-group">
            				<div class="col-md-3">
            					<label class="control-label" for="awsaccesskey"><?php echo _("AWS Access Key") ?></label>
            				</div>
            				<div class="col-md-9">
            					<div class="input-group">
            						<input type="text" class="form-control" id="awsaccesskey" name="awsaccesskey" value="<?php echo isset($awsaccesskey)?$awsaccesskey:''?>"<?php echo $disabled?>>
            						<span class="input-group-addon" id="awskeyaddon"><a href="http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSGettingStartedGuide/AWSCredentials.html" target="_blank"><?php echo _("What's this?")?></a></span>
            					</div>
            				</div>
            			</div>
            		</div>
            	</div>
            	<!--END AWS Access Key-->
            	<!--AWS Secret-->
            	<div class="element-container">
            		<div class="row">
            			<div class="form-group">
            				<div class="col-md-3">
            					<label class="control-label" for="awssecret"><?php echo _("AWS Secret") ?></label>
            				</div>
            				<div class="col-md-9">
            					<div class="input-group">
            						<input type="text" class="form-control" id="awssecret" name="awssecret" value="<?php echo isset($awssecret)?$awssecret:''?>"<?php echo $disabled?>>
            						<span class="input-group-addon" id="awssecretaddon"><a href="http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSGettingStartedGuide/AWSCredentials.html" target="_blank"><?php echo _("What's this?")?></a></span>
            					</div>
            				</div>
            			</div>
            		</div>
            	</div>
            	<!--END AWS Secret-->
            </form>
					</div>
				</div>
			</div>
		</div>
	</div>
  <p class ="pull-right"> For use with Amazon S3&trade;</p>
</div>
<script type="text/javascript">
  var immortal = <?php echo (isset($immortal) && !empty($immortal))?'true':'false';?>;
	$('#server_form').on('submit', function(e) {
			if($("#bucket").val().length === 0 ) {
				warnInvalid($("#host"),_("The host cannot be empty"));
				return false;
			}else{
				return true;
			}
	});
</script>
