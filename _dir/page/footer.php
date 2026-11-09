<?php
if(!defined('DIR_INIT'))exit();
?>
<?php if(!empty($conf['footer_bar']) && $conf['footer_bar'] == 1){?>
<footer class="footer card-footer mt-3" id="footer">
<span> Copyright &copy;<?php echo date('Y')?> <?php echo h($conf['title'])?>  <?php echo $conf['footer']?></span>
</footer>
<?php }?>
<script src="<?php echo $cdnpublic?>jquery/3.7.1/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>popper.js/1.16.1/umd/popper.min.js"></script>
<script src="<?php echo $cdnpublic?>twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="./_dir/static/js/clipBoard.min.js"></script>
