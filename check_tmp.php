<?php
use App\Models\ModerationItem;
use App\Models\Profile;

echo 'approved='.Profile::where('moderation_status', 'APPROVED')->count()."\n";
echo 'pending='.Profile::where('moderation_status', 'PENDING')->count()."\n";
echo 'discoverable='.Profile::discoverable()->count()."\n";
echo 'profile queue='.ModerationItem::where('entity_type', 'PROFILE')->where('status', 'QUEUED')->count()."\n";

$m = ModerationItem::whereNotNull('matched_words')->first();
echo 'flagged item='.($m ? $m->entity_id.' words='.$m->matched_words.' P'.$m->priority : 'none')."\n";
