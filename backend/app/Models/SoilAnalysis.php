<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SoilAnalysis extends Model { protected $fillable=['organization_id','farm_id','field_id','block_id','sample_reference','sampled_at','ph','ec','organic_matter_percent','moisture_percent','laboratory','notes']; protected function casts(): array { return ['sampled_at'=>'date','ph'=>'decimal:2','ec'=>'decimal:3','organic_matter_percent'=>'decimal:3','moisture_percent'=>'decimal:3']; } }
