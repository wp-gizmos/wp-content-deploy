# wp-content-deploy
WordPress Plugin for selectively batched content deployment from staging to production

## Dependencies
This plugin relies on the post GUID value to sync posts. As a result, there are a coouple of additional plugins you might need to use.
If you use Advanced Custom Fields, the relationship field is using the Post ID, instead of the guid, so you'll need a plugin that filters ACF to use the guid for relationships.

Also, the GUID is typically a permalink, and if you are migrating sites, you might end up replacing that url. GUIDs need to use an environment independent value.
