ISZ Expertise Service
========================

Построение модели экспертизы c помощью Visual Paradigm 12
 
 - User task 
    - WAIT TASK - предназначен для приостоновки процесса пока его не сдвинит родительский или дочерний процесс. 
    применим когда необходимо заблокировать отображение каких либо элементов пользователю
    - USER CONTROLS - предназначен для отображение наборов елентов формы соглосования и комментирования
    Формат: 
    "controls": [
          {
            "title": "Отправить на доработку",
            "value": "",
            "type": "button",
            "name": "op_draft",
            "params": {
              "op": "sendToDraft",
            }
          }
    ]
    Сервис имеет наглость дописывать там где он считает нужным, различную системную информацию, например некий ID
    Если необходимо отобразить историю комментриев с набором коментируемых полей - необходимо указать в USER CONTROLS
    в разделе "Properties" пустой элемент "comments"
    
 - Service Task
    - TRACE - просто сквозная таска без смысла, кроме как на запасной случай
    - ACTION
        - ACTION RPC - позволяет обратиться к серверу и записать/обновить набор переменных в процесс
          Доступные RPC (все перечисленные входные параметры обязательны, в противном случае процесс блокируеться или же останавливаеться)
          
            - %{expertise.api.startExpertise} Создание дерева экспертиз и начало соглосования
                - Параметры: groupId, entityId, entityType
                - Ответ: {"status":"success","data":{"expertiseId":"5d62569a-3114-4656-bcb8-c3754d0f60ce","stepComplete":1}}
                - REST: app.isz.gosbook.ru/api/expertise/push?params[id]=19845554-b36d-439f-9546-80c3800733aa&params[op]=saveFieldExpertise&params[commentField]=root&params[commentBody]=autor
            - %{expertise.api.getStatusExpertise} Получение статуса экспертизы (внимание в каждой группе при незаконченной экспертизы статус свой)
                - Параметры: groupId, entityId, entityType, userId, userRole
                - Ответ: {"status":"success","data":{"expertiseStatus":"status","stepComplete":1, "expertiseId": "", "controls":{}, "comments":{}}}
                  Запишит в процесс переменую ${expertiseStatus=???} и ${$stepComplete=1}
                  
            - %{expertise.api.saveUserExpertise} Сохранение экспертиз пользователей в дереве, сохранение статуса (операция фоновая)
                - Параметры: берет с процесса и ненуждаеться в других
                - Ответ: Запишит в процесс переменую ${$stepComplete=1}
                
            - %{expertise.api.saveFieldExpertise} Сохранение данных по коментарию, вызывать после PUSH операции на контроле
                - Парметры: {"op": "saveFieldExpertise", "id": "19845554-b36d-439f-9546-80c3800733aa", "commentField": "root", "commentValue": "", "commentBody": ""}
                - Ответ: Запишит в процесс переменую ${$stepComplete=1}
                
            - %{expertise.api.pushExpertise} - основной метод всей экспертизы. Выполяеться тогда когда нам необходимо процесс подвинуть далее по модели
                - Параметры: {op:"что то нажали"}
                - Ответ: зависит от скопа выполненых тасок
                
            - %{expertise.api.statusChildrenUserExpertise} - вспомогательная, определит статус дочерних экспертиз
                  - Параметры: необходимо расчитывать что это актуально применять в блоке парент
                  - Ответ: Запишит в процесс переменую ${expertiseStatus=???}
                  
            - %{expertise.api.pushParentUserExpertise} - двинет родительский процесс, если он будет найденн
                 - Параметры: необходимо расчитывать что это актуально применять в блоке парент
                 - Ответ: Запишит в процесс переменую ${expertiseStatus=???}
                 
	        - %{expertise.api.pushChildrenUserExpertise} - двинет дочернии процессы, если они будут найдены
	             - Параметры: 
	             - Ответ: Запишит в процесс переменую ${expertiseStatus=???}
	            
	        
 - GATEWAY 
 - REST
    
  Сохранить значение коментария по полю или сущности а так сдвигать процесс
  - app.isz.gosbook.ru/api/expertise/push?params[id]=19845554-b36d-439f-9546-80c3800733aa&params[op]=saveFieldExpertise&params[commentField]=root&params[commentBody]=autor
  Запустить эксертизу сущности
  - app.isz.gosbook.ru/api/expertise/start?groupId=41a0bf0a-b6de-437a-a8cf-3795ba90ba95&entityId=5d62569a-3114-4656-bcb8-c3754d0f60ce&entityType=lot
  Получить статус документов
  - app.isz.gosbook.ru/api/docs/expertise/5d62569a-3114-4656-bcb8-c3754d0f60ce
 
 
 


-------------------------------------
REST Api серсив не прендназначен для прямого взаимодействия по ресту, но тем неменее имеет интерфейс