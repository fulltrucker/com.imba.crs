<div id="help">
  {ts}Use this form to select how the Region and Chapter are assigned to contributions for this event.{/ts}
</div>

<div id="id_Sharing" class="crm-block crm-form-block crm-contribution-contributionpage-sharing-form-block">

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

  <table class="form-layout-compressed">
    <tr>
      <td class="label">{$form.region_mode.label}</td>
      <td>{$form.region_mode.html}{$form.region_contact_id.html}</td>
    </tr>
    <tr>
      <td class="label">{$form.chapter_mode.label}</td>
      <td>{$form.chapter_mode.html}{$form.chapter_contact_id.html}</td>
    </tr>
  </table>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

</div>

<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    CRM.crs = {
      rblank : $('#CIVICRM_QFID_0_region_mode'),
      region: $('#CIVICRM_QFID_3_region_mode'),
      region_label: $('label[for="CIVICRM_QFID_3_region_mode"'),
      region_contact_id: $('#region_contact_id'),
      region_use: $('#CIVICRM_QFID_1_region_mode'),
      cblank:  $('#CIVICRM_QFID_0_chapter_mode'),
      chapter: $('#CIVICRM_QFID_1_chapter_mode'),
      chapter_contact_id: $('#chapter_contact_id'),

      regionSelect: function() {
        if (CRM.crs.region_contact_id.val())
          CRM.crs.region_use.prop('checked', true);
        else
          CRM.crs.rblank.prop('checked', true);
      },
      chapterSelect: function() {
        var val = CRM.crs.chapter_contact_id.val();

        if (val) {
          CRM.crs.allowSelectedChapter(true);
          CRM.crs.chapter.prop('checked', true);
        }
        else {
          CRM.crs.allowSelectedChapter(false);
          CRM.crs.cblank.prop('checked', true);
        }

        return false;
      },
      allowSelectedChapter: function(allow) {
        CRM.crs.region.prop('disabled', !allow);
        if (allow)
          CRM.crs.region_label.removeClass('disabled');
        else {
          CRM.crs.region_label.addClass('disabled');
          if (CRM.crs.region.prop('checked'))
            CRM.crs.rblank.prop('checked', true);
        }
      }
    }
    CRM.crs.cblank.click(function() {
      CRM.crs.allowSelectedChapter(false);
    });
    CRM.crs.chapter.click(function() {
      CRM.crs.chapterSelect();
    });
    CRM.crs.region_contact_id.change(function() {
      CRM.crs.regionSelect();
    });
    CRM.crs.chapter_contact_id.change(function() {
      CRM.crs.chapterSelect();
    });

    CRM.crs.allowSelectedChapter(CRM.crs.chapter.prop('checked'));

  });
  {/literal}
</script>
