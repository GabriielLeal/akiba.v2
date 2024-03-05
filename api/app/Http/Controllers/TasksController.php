<?php

namespace App\Http\Controllers;

use App\Models\Tasks;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *      name="Tarefas",
 *      description="Esta seção oferece acesso a operações relacionadas as tarefas da equipe cadastradas no sistema da Rede Akiba."
 * )
 */
class TasksController extends Controller
{
    //--------------Retorna todas as tarefas cadastradas--------------
    /**
     * @OA\Get(
     *      path="/api/tarefas",
     *      tags={"Tarefas"},
     *      summary="Retorna todas as tarefas cadastrados",
     *      description="Este endpoint retorna uma lista completa de todas as tarefas da equipe cadastradas no sistema da Rede Akiba.",
     *      @OA\Response(
     *          response=200,
     *          description="Lista de tarefas cadastradas",
     *          @OA\JsonContent(ref="#/components/schemas/TasksResponse"),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Nenhuma tarefa cadastrada",
     *          @OA\JsonContent( 
     *              @OA\Property(property="error", type="string", example="Nenhuma tarefa cadastrada")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Ocorreu um erro de processamento",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Ocorreu um erro de processamento")
     *          ),
     *      ),
     * )
     */
    public function index()
    {
        try{
            $tasks = Tasks::with('responsible')->get();

            if($tasks->isEmpty()){
                return response()->json(['message' => 'Nenhuma tarefa cadastrada'], 404);
            }

            return response()->json(['message' => 'Lista de tarefas cadastradas', 'tarefas' => $tasks], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Ocorreu um erro de processamento', 'error' => $e->getMessage()], 500);
        }
    }

    //--------------Cadastra uma nova tarefa--------------
    /**
     * @OA\Post(
     *      path="/api/tarefas",
     *      tags={"Tarefas"},
     *      summary="Cadastra uma nova tarefa",
     *      description="Este endpoint realiza o cadastro de uma nova tarefa da equipe no sistema da Rede Akiba.",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/TasksRequest"),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Tarefa cadastrada com sucesso",
     *          @OA\JsonContent(ref="#/components/schemas/TasksResponse"),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Ocorreu um erro de validação",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Ocorreu um erro de validação"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Usuário não encontrado",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Usuário não encontrado"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Ocorreu um erro de processamento",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Ocorreu um erro de processamento"),
     *          ),
     *      ),
     * )
     */
    public function store(Request $request)
    {
        try{
            $messages = [
                'responsible.required' => 'O campo responsible é obrigatório',
            ];

            $request->validate([
                'responsible' => 'required',
            ]);

            $responsible = Users::find($request->responsible);
            if(!$responsible){
                return response()->json(['message' => 'Usuário não encontrado'], 404);
            }

            $tasks = new Tasks();
            $tasks->responsible = $request->responsible;
            $tasks->content = $request->content;
            $tasks->status = $request->status;
            $tasks->save();

            //Associa o programa ao usuário responsável
            $responsible->tasks()->sabe($tasks);

            //Retorna a tarefa com os dados do usuário responsável
            $tasks->load('responsible');

            return response()->json(['message' => 'Tarefa cadastrada com sucesso', 'tarefa' => $task], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => 'Ocorreu um erro de validação', 'error' => $e->errors()], 400);
        }catch(\Exception $e){
            return response()->json(['message' => 'Ocorreu um erro de processamento', 'error' => $e->getMessage()], 500);
        }
    }

    //--------------Retorna uma tarefa específica--------------
    /**
     * @OA\Get(
     *      path="/api/tarefas/{slug}",
     *      tags={"Tarefas"},   
     *      summary="Retorna uma tarefa específica",
     *      description="Este endpoint retorna uma tarefa específica da equipe cadastrada no sistema da Rede Akiba.",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug da Tarefa: Retorna uma tarefa específica baseada no slug fornecido.",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Tarefa encontrada",
     *          @OA\JsonContent(ref="#/components/schemas/TasksResponse"),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Tarefa não encontrada",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Tarefa não encontrada"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Ocorreu um erro de processamento",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Ocorreu um erro de processamento"),
     *          ),
     *      ),
     * )   
     */
    public function show($slug)
    {
        try{
            $tasks = Tasks::with('responsible')->where('slug', $slug)->first();

            if(!$tasks){
                return response()->json(['message' => 'Tarefa não encontrada'], 404);
            }

            return response()->json(['message' => 'Tarefa encontrada', 'tarefa' => $tasks], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Ocorreu um erro de processamento', 'error' => $e->getMessage()], 500);
        }
    }

    //--------------Atualiza uma tarefa especifica------------
    /**
     * @OA\Patch(
     *      path="/api/tarefas/{id}",
     *      tags={"Tarefas"},
     *      summary="Atualiza uma tarefa específica",
     *      description="Este endpoint atualiza uma tarefa específica da equipe cadastrada no sistema da Rede Akiba.",
     *      @OA\Parameter(
     *          name="id",  
     *          description="Id da Tarefa: Atualiza uma tarefa específica baseada no Id fornecido.",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/TasksRequest"),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Tarefa atualizada com sucesso",
     *          @OA\JsonContent(ref="#/components/schemas/TasksResponse"),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Tarefa ou usuário não encontrado(a)",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Tarefa ou usuário não encontrado(a"),
     *          ),
     *      ),
     *      @OA\Response(       
     *          response=500,
     *          description="Ocorreu um erro de processamento",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Ocorreu um erro de processamento"),
     *          ),
     *      ),
     * )
     */
    public function update(Request $request, $id)
    {
        try{
            $tasks = Tasks::find($id);

            if(!$tasks){
                return response()->json(['message' => 'Tarefa não encontrada'], 404);
            }

            if($request->hast('responsible')){
                $responsible = Users::find($request->responsible);
                if($responsible){
                    $responsible->tasks()->save($tasks);
                }else{
                    return response()->json(['message' => 'Usuário não encontrado'], 404);
                }
            }

            if($request->hast('content')){
                $tasks->content = $request->content;
            }

            if($request->hast('status')){
                $tasks->status = $request->status;
            }

            $tasks->save();

            //Retorna a tarefa com os dados do usuário responsável
            $tasks->load('responsible');

            return response()->json(['message' => 'Tarefa atualizada com sucesso', 'tarefa' => $tasks], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Ocorreu um erro de processamento', 'error' => $e->getMessage()], 500);
        }
    }

    //--------------Remove uma tarefa------------
    /**
     * @OA\Delete(
     *      path="/api/tarefas/{id}",
     *      tags={"Tarefas"},
     *      summary="Remove uma tarefa específica",
     *      description="Este endpoint remove uma tarefa específica da equipe cadastrada no sistema da Rede Akiba.",
     *      @OA\Parameter(
     *          name="id",
     *          description="Id da Tarefa: Remove uma tarefa específica baseada no Id fornecido.",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Tarefa removida com sucesso",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Tarefa removida com sucesso"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Tarefa não encontrada",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Tarefa não encontrada"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Ocorreu um erro de processamento",
     *          @OA\JsonContent(
     *              @OA\Property(property="error", type="string", example="Ocorreu um erro de processamento"),
     *          ),
     *      ),
     * )
     */
    public function destroy($id)
    {
        try{
            $tasks = Tasks::find($id);

            if(!$tasks){
                return response()->json(['message' => 'Tarefa não encontrada'], 404);
            }

            $tasks->delete();

            return response()->json(['message' => 'Tarefa removida com sucesso'], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Ocorreu um erro de processamento', 'error' => $e->getMessage()], 500);
        }
    }
}
