refactor into SOLID pattern

Rules:
- Refer into Shift Module
    - \App\Http\Controllers\ShiftController
    - \App\Interfaces\Services\Shift\ShiftServiceInterface
    - \App\Http\Services\Shift\ShiftService
    - \App\Interfaces\Repositories\Shift\ShiftRepositoryInterface
    - \App\Http\Repositories\Shift\ShiftRepository
    - \App\Policies\ShiftPolicy

- for instance if we create SupervisorRequestSchedule, we must to create
    1. SupervisorRequestScheduleController
    2. SupervisorRequestScheduleServiceInterface
    3. SupervisorRequestScheduleService
    4. SupervisorRequestScheduleRepositoryInterface
    5. SupervisorRequestScheduleRepository
    6. SupervisorRequestSchedulePolicy

- register service and repository into AppServiceProvider

- move all logic in controller into service

- implement Gate in controller and registered it into AuthServiceProvider

- we can use base service or repository for common CRUD operation, if CRUD operation need specific action, just override the funciton in it's service or repository.

- don't forget to create Policy file, and use Gate. Gate refer from controller constructor if any, and use it as in the constructor, dont't use gate in controller function if isn't exist in constructor.

- make it as same as ShiftController. also in index function, use service instead of using QueryBuilder directly. please pay attention. including variable name, like $data and $datas in index()

- you should set return as create function return createdResponse(), update function return updatedResponse(), destroy function return deletedResponse(), forceDelete function return forceDeletedResponse(), restore function return restoredResponse()