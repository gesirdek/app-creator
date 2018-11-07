<?php

namespace App;
use App\Entities\SearchMetum;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PharIo\Manifest\Application;

class Helper
{
    public static function filter_request(Request $request, $model){
        if($request->has('per_page')){
            $perPage = $request->get('per_page');
            if ( $perPage == -1 ) {
                $perPage = $model->count();
            }
        }else{
            $perPage = 5; //default value
        }

        if($request->has('keyword')){
            $keyword = $request->get('keyword');
            if($request->has('filter')) {
                try {
                    $filters = json_decode($request->get('filter'));
                } catch (\Exception $e) {
                    $filters = $request->get('filter');
                }


                if (strlen($keyword) > 1) {
                    $model = $model
                        ->where(function ($query) use ($keyword, $filters) {
                            foreach ($filters as $filter) {
                                if (is_object($filter)) {
                                    $relation = $filter->relation;
                                    $query->orWhereHas($relation, function ($q) use ($keyword, $filter) {
                                        $q->where(function ($q) use ($keyword, $filter) {
                                            foreach ($filter as $key => $value) {
                                                if ($key === 'relation')
                                                    continue;

                                                $q->orWhere($value, 'LIKE', '%' . $keyword . '%');
                                            }
                                        });
                                    });
                                } else {
                                    $query->orWhere($filter, 'LIKE', '%' . $keyword . '%');
                                }
                            }
                        })->distinct();
                }
            }else{
                if(strlen($keyword) > 1){
                    $searchMeta = new SearchMetum();
                    $result = $searchMeta->where('foreign_key',str_singular($model->getTable()).'_id')->first();

                    if($result != null)
                        return $model->
                        where($result->search_in,'like', '%'.$request->get('keyword').'%')
                        ->orWhere($result->search_in,'ilike', '%'.$request->get('keyword').'%')->get();//only postgresql
                    else
                        return null;
                }
            }


            return $model->paginate($perPage);
        }elseif($request->has('per_page')){
            return $model->paginate($perPage);
        }
        return $model->get();
    }
}