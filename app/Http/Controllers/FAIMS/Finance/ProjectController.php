<?php

namespace App\Http\Controllers\FAIMS\Finance;

use App\Http\Controllers\Controller;
use App\Models\ListProject;
use App\Services\FAIMS\Finance\ProjectClass;
use App\Services\FAIMS\Finance\ViewClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
      use HandlesTransaction;

    public $project, $view, $dropdown;

    public function __construct(
        ProjectClass $project, 
        ViewClass $view, 
        DropdownClass $dropdown
    ){
        $this->project = $project;
        $this->view = $view;
        $this->dropdown = $dropdown;
    }
    

     public function index(Request $request){
        switch($request->option){     
            case 'lists':
                  return $this->project->lists($request);
            break;

            default:
                return inertia('Modules/FAIMS/Finance/Projects/Index', [
                    'dropdowns' => [
                        'project_types' => $this->dropdown->dropdowns('Project Type'),
                        'roles'  =>  \Auth::user()->roles,
                    ],
                ]); 
        }   
    }

    public function store(Request $request) {

        $result = $this->handleTransaction(function () use ($request) {
            return $this->project->save($request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
        
    }

    public function show(ListProject $listProject)
    {
        //
    }

    public function edit(ListProject $listProject)
    {
        //
    }

    public function update(Request $request, ListProject $listProject) {
        $request->merge(['id' => $listProject->id]);
        $result = $this->handleTransaction(function () use ($request) {
            return $this->project->update( $request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);

    }

  
    public function destroy(ListProject $listProject)
    {
        $listProject->delete();
        return back()->with([
            'message' => 'Project deleted successfully!',
            'info' => "You've successfully deleted Project.",
            'status' => 'success',
        ]);
    }
}
?>

