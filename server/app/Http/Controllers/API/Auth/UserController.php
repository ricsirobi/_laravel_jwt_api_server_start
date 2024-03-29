<?php

namespace App\Http\Controllers\API\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use App\Models\Mission;
use App\Models\BattleRound;
use App\Models\Item;

class UserController extends Controller
{
    public function register(Request $request)
{
    $request->validate([
        'username' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    $user = User::create([
        'username' => $request->username,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    return response()->json([
        'message' => 'Registration successful',
        'user' => $user,
    ], 201);
}

public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$user = User::where('email', $request->email)->first()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = JWTAuth::fromUser($user);
        
        return response()->json([
            'access_token' => $token,
        ], 200);
    }

    private static function authenticateUser(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if(!$user)
            {
                return response()->json(['user_not_found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $user;
    }

    /*
    public function getUserDetails(Request $request)
    {
        $user = self::authenticateUser($request);

        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }
        $user->level = $user->level;

        $active_missions = $user->activeMission;
        $completed_missions = $user->completedMission;
        $acceptable_missions =  $user->acceptableMission;
        $equipped_items = $user ->equipped_items;
        $backpack_items = $user->backpack_items;

        $user->active_missions = $active_missions;
        $user->completed_missions = $completed_missions;
        $user->acceptable_missions = $acceptable_missions;
        $user->equipped_items = $equipped_items;
        $user->backpack_items = $backpack_items;
        
        return response()->json([
            'user' => $user,
             
        ]);
    }

    public function buyFuel(Request $request)
    {
        $user = self::authenticateUser($request);

        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }
        if($user->nanocrystal < 1)
        {
            return response()->json([
                'Error' => 'Not enough nanocrystal',
            ]);
        }
        if($user->fuel > 7200*0.8)
        {
            return response()->json([
                'Error' => 'Not enough space in fuel tank',
            ]);
        }
        $user->nanocrystal -= 1;
        $user->fuel += 7200*0.2;
        $user->save();
        
        //todo: tranzakció
        return response()->json([
            'Success' => 'Fuel bought',
        ]);
    }

    public function acceptMission(Request $request)
    {
        $user = self::authenticateUser($request);

        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }
        if($user->active_mission!==null)
        {
            return response()->json([
                'Error' => 'You already have an active mission',
            ]);
        }

        $mission = Mission::find($request->mission_id);
        if($user->fuel < $mission->seconds)
        {
            return response()->json([
                'Error' => 'Not enough fuel',
            ]);
        }
        $user->fuel -= $mission->fuel_cost;
        $user->missions()->attach($mission->id, ['willComplete' => now()->addSeconds($mission->seconds)]);
        $user->save();

        return response()->json([
            'Success' => 'Mission accepted',
        ]);
    }

    public function seenFight(Request $request)
    {
        $user = self::authenticateUser($request);
        $mission = Mission::find($request->missionId);
        //dd($mission);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }
        if($user->active_mission===null)
        {
            return response()->json([
                'Error' => 'You dont have an active mission',
            ]);
        }
        if($user->active_mission->id !== $request->missionId)
        {
            return response()->json([
                'Error' => 'This is not your active mission',
            ]);
        }
        $user->missions()->updateExistingPivot($request->missionId, ['saw' => 1]);
        //az usernek meg kell kapnia a rewardokat
        $user->xp += $mission->xp_reward;
        $user->credit += $mission->credit_reward;
        $nanocrystal_chance = rand(0,100);
        if($nanocrystal_chance <= $mission->nanocrystal_chance_reward)
        {
            $user->nanocrystal += 1;
        }
        if($mission->reward_item_id !== null)
        {
            $user->items()->attach($mission->reward_item_id);
        }
        $user->integrity += $mission->monster->integrity/2;

        $user->save();
        return response()->json([
            'Success' => 'Fight seen',
            'Rewards'=> [
                'xp' => $mission->xp_reward,
                'credit' => $mission->credit_reward,
                'nanocrystal' => $nanocrystal_chance <= $mission->nanocrystal_chance_reward?1:0,
                'item' => $mission->reward_item_id??null,
            ]
        ]);
    }

    public function doBattle(Request $request)
    {
        $mission = Mission::find($request->missionId);
        //először leellenőrizzük, hogy a missionja lejárt az usernek
        $user = self::authenticateUser($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }
        if($user->active_mission===null)
        {
            return response()->json([
                'Error' => 'You dont have an active mission',
            ]);
        }
        if($user->active_mission->id !== $request->missionId)
        {
            return response()->json([
                'Error' => 'This is not your active mission',
            ]);
        }
        if($user->active_mission->pivot->willComplete > now())
        {
            return response()->json([
                'Error' => 'You have to wait until the mission ends',
            ]);
        }
         $battleRounds = BattleRound::where('mission_id', $request->missionId)->where('character1_id', $user->id)->where('character2_id', $mission->monster->id)->get();
         if(!$battleRounds->isEmpty())
        {
            return response()->json([
                'Success' => 'Battle already done',
                'User won' => $battleRounds->last()->character1_hp > 0,
            ]);
        }
        $battleRounds = [];
        $attackerHP = $user->hp*100*$user->level;
        $monsterHP = $mission->monster->hp*70*$mission->monster->level;
        $currentAttacker = $user;
        $round_number = 1;
        
        while ($attackerHP > 0 && $monsterHP > 0) {
            $damage = $this->calculateDamage($currentAttacker);
            if ($currentAttacker === $user) {
                $monsterHP -= round($damage);
            } else {
                $attackerHP -=  round($damage);
            }
            $battleRounds[] = [
                'mission_id' => $request->missionId,
                'round_number' => $round_number,
                'character1_id' => $user->id,
                'character2_id' => $mission->monster->id,
                'character1_hp' => $attackerHP,
                'character2_hp' => $monsterHP,
                'attacker' => $currentAttacker->id,
                'damaged' => $damage
            ];
            if($currentAttacker === $mission->monster)
            {
                $currentAttacker = $user;
            }
            else
            {
                $currentAttacker = $mission->monster;
            }
            $round_number++;
        }
        
        $won = null;
        if($attackerHP > 0)
        {
            $won = $user->id;
        }
        if($monsterHP > 0)
        {
            $won = $mission->monster->id;
        }
        $battleResult = [
            'rounds' => $battleRounds,
            'attackerHP' => $attackerHP,
            'monsterHP' => $monsterHP,
            'won' => $won,
        ];
        
        
        $this->saveBattleResult($battleResult, $request->missionId);
        
        if($won === $user->id)
        {
            $user->missions()->updateExistingPivot($request->missionId, ['user_won' => 1]);
        }
        else
        {
            $user->missions()->updateExistingPivot($request->missionId, ['user_won' => 0]);
        }
        $user->save();

        return response()->json([
            'Success' => 'Battle done',
            'User won' => $won === $user->id,
        ]);

    }

    private static function calculateDamage($attacker)
    {
        $damage = $attacker->damage;
        $luck = $attacker->luck;
        $gear_bonus = $attacker->gear_bonus;
        $int = $attacker->intelligence;
        $shield = $attacker->shield;
        $integrity = $attacker->integrity;
        $damage = $damage + $gear_bonus + $int + $luck;
        $damage = $damage * (1 + $integrity/100);
        $damage = $damage * (1 - $shield/100);
        return $damage;
    }

    private static function saveBattleResult($battleResults, $missionId)
    {   
        
        $rounds = $battleResults['rounds'];
        foreach($rounds as $round)
        {
            $values = [
                "mission_id" => $missionId,
                "round_number" => $round['round_number'],
                "character1_id" => $round['character1_id'],
                "character2_id" => $round['character2_id'],
                "character1_hp" => $round['character1_hp'],
                "character2_hp" => $round['character2_hp'],
                "attacker" => $round['attacker'],
                "damaged" => $round['damaged'],
            ];
            BattleRound::create($values);
        }
    }

    public function fightInfo(Request $request)
    {
        //van missionId a body-ban
        //és nyilván user
        $user = self::authenticateUser($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }
        $mission = Mission::find($request->missionId);
        $battleRounds = BattleRound::where('mission_id', $request->missionId)->where('character1_id', $user->id)->where('character2_id', $mission->monster->id)->get();
        if($battleRounds->isEmpty())
        {
            return response()->json([
                'Error' => 'No battle found',
            ]);
        }
        $battleResult = [
            'rounds' => $battleRounds,
            'attackerHP' => $battleRounds->last()->character1_hp,
            'monsterHP' => $battleRounds->last()->character2_hp,
            'won' => $battleRounds->last()->character1_hp > 0?1:0,
        ];
        return response()->json([
            'Success' => 'Battle found',
            'Battle' => $battleResult,
            'Winner' => $battleRounds->last()->character1_hp > 0?$user:$mission->monster,
            'character'=>$user,
            'monster'=>$mission->monster,
        ]);
    }

    public function sellItem(Request $request)
    {
        $user = self::authenticateUser($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }

        $item = $user->items()->where('item_id', $request->itemId)->first();
        if($item === null)
        {
            return response()->json([
                'Error' => 'Item not found',
            ]);
        }
        $user->credit += $item->pivot->price;
        $user->items()->detach($request->itemId);
        $user->save();
        return response()->json([
            'Success' => 'Item sold',
        ]);
    }


    public function equipItem(Request $request)
    {
        $user = self::authenticateUser($request);
    
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
    
        $item = $user->items()->where('item_id', $request->itemId)->first();
    
        if ($item === null) {
            return response()->json([
                'Error' => 'Item not found',
            ]);
        }
    
        $type = $item->type;
    
        // Ellenőrizze, hogy van-e ugyanolyan típusú, de más helyen felvett elem
        $existingItem = $user->items()->where('type', $type)->where('place', 1)->first();
    
        if ($existingItem !== null) {
            $existingItem->pivot->place = 0;
            $existingItem->pivot->save();
        }
    
        if ($item->pivot->place === 1) {
            return response()->json([
                'Error' => 'Item already equipped',
            ]);
        }
    
        $user->items()->updateExistingPivot($request->itemId, ['place' => 1]);
        $user->save();
    
        return response()->json([
            'Success' => 'Item equipped',
        ]);
    }
    
    public function unequipItem(Request $request)
    {
        $user = self::authenticateUser($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }

        $item = $user->items()->where('item_id', $request->itemId)->first();
        if($item === null)
        {
            return response()->json([
                'Error' => 'Item not found',
            ]);
        }
        if($item->pivot->place === 0)
        {
            return response()->json([
                'Error' => 'Item already unequipped',
            ]);
        }
        $user->items()->updateExistingPivot($request->itemId, ['place' => 0]);
        $user->save();
        return response()->json([
            'Success' => 'Item unequipped',
        ]);
    }

    
    public function buyItem(Request $request)
    {
        $user = self::authenticateUser($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Visszatér a hibaüzenettel, ha van
        }

        $item = Item::find($request->itemId);
        if($item === null)
        {
            return response()->json([
                'Error' => 'Item not found',
            ]);
        }
        if($user->credit < $item->price)
        {
            return response()->json([
                'Error' => 'Not enough credit',
            ]);
        }
        $user->credit -= $item->price;
        $user->items()->attach($request->itemId);
        $user->save();
        return response()->json([
            'Success' => 'Item bought',
        ]);
    }*/
}
