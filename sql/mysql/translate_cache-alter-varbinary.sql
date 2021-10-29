ALTER TABLE translate_cache
  MODIFY tc_key VARBINARY(255) NOT NULL;

ALTER TABLE translate_groupreviews
  MODIFY tgr_group VARBINARY(200) NOT NULL,
  MODIFY tgr_lang VARBINARY(20) NOT NULL;

ALTER TABLE translate_groupstats
  MODIFY tgs_group VARBINARY(100) NOT NULL,
  MODIFY tgs_lang VARBINARY(20) NOT NULL;

ALTER TABLE translate_messageindex
  MODIFY tmi_key VARBINARY(255) NOT NULL,
  MODIFY tmi_value VARBINARY(255) NOT NULL;

ALTER TABLE translate_metadata
  MODIFY tmd_group VARBINARY(200) NOT NULL,
  MODIFY tmd_key VARBINARY(20) NOT NULL;

ALTER TABLE translate_sections
  MODIFY trs_key VARBINARY(255) NOT NULL;

ALTER TABLE translate_stash
  MODIFY ts_title VARBINARY(255) NOT NULL;

ALTER TABLE translate_tms
  MODIFY tms_lang VARBINARY(20) NOT NULL;

ALTER TABLE translate_tmt
  MODIFY tmt_lang VARBINARY(20) NOT NULL;
