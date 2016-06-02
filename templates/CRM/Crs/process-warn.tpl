{literal}

<style type="text/css">

  @keyframes processing {
    0% { color: white; }
    50% { color: #8f0002; }
    100% { color: white; }
  }

  #process-warning {
    position: fixed;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
    top: 0px;
    left: 0px;
    z-index: 10000;
    display: none;
  }
  #process-warning div {
    background-color: #8f0002;
    color: white;
    padding: 17px;
    text-align: left;
    position: fixed;
    width: 50%;
    left: 25%;
    top: 25%;
    border: 1px solid maroon;
  }
  #process-warning h2 {
    font-size: 36px;
    font-weight: normal;
    line-height: 56px;
    margin: 0px;
    animation-name: processing;
    animation-duration: 2s;
    animation-iteration-count: infinite;
  }
  #process-warning img {
    width: 200px;
    height: 201px;
    float: left;
    margin-right: 25px;
  }
  #process-warning p {
    font-size: 24px;
    line-height: 1.25em
  }
</style>

<script type="text/javascript">

  var submitOnceCRS = submitOnce;

  submitOnce = function() {
    CRM.$('#process-warning').css('display', 'block');
    return submitOnceCRS.apply(this, arguments);
  }

</script>

{/literal}

<div id="process-warning">
  <div>
    <h2>
      <img src="https://www.imba.com/sites/default/files/trail-love-logo-black-trans.png" alt="Trail Love" title="Trail Love" />
      Processing...
    </h2>
    <p>This may take a few minutes. Please do not resubmit.</p>
    <p style="clear:both"></p>
  </div>
</div>