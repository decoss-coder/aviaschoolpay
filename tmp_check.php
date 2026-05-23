$rows = App\Models\Eleve::whereNotNull('matricule_desps')->take(10)->get();
foreach ($rows as $e) {
  $u = $e->user_id ? App\Models\User::find($e->user_id) : null;
  echo $e->matricule_desps . ' user_id=' . $e->user_id . ' role=' . ($u ? $u->role : 'NULL') . PHP_EOL;
}
