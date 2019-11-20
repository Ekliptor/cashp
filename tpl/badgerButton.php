<div class="cashp-button-wrap">
  <button
    class="cashp-button <?php echo (isset($btnConf['cssClass']) ? $btnConf['cssClass'] : '');?>"
    data-to="<?php echo $btnConf['address'];?>"
    data-satoshis="<?php echo $btnConf['sats'];?>"
    <?php if($btnConf['useTokenPayments'] === true):?>
    	data-tokens="<?php echo $btnConf['tokenAmount'];?>"
    	data-token-id="<?php echo $btnConf['tokenID'];?>"
    <?php endif;?>
    <?php if($btnConf['callback']):?>
    	data-success-callback="<?php echo $btnConf['callback'];?>"
    <?php endif;?>
      ><?php echo $btnConf['text'];?></button>
    </div>
<?php if ($btnConf['includedButtonCode'] === false || $btnConf['forceIndludeJs'] === true): $btnConf['includedButtonCode'] = true;?>
<script type="text/javascript"><?php echo $btnConf['script']?></script>
<script type="text/javascript" src="<?php echo $btnConf['buttonLibSrc'];?>"></script>
<?php endif;?>