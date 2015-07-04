###
 * This file is part of the RIFilemanagerBundle package.
 *
 * (c) Rafal Ignaszewski <https://github.com/qjon>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
###
class CopyPaste extends Service
  constructor: ($http, modalService, selectionService, dirStructureService, SpinnerService) ->
    ###*
     * 0 - no action set
     * 1 - cut
     * 2- copy
     *
     * @type {number}
     ###
    @actionType = 0
    @$http = $http
    @modalService = modalService
    @selectionService = selectionService
    @dirStructure = dirStructureService
    @spinnerService = SpinnerService

  ###*
   * Execute copy or move action
   *
   * @param {int} destDirId
   ###
  doAction: (destDirId) ->
    switch @actionType
      when 1 then @move destDirId
      when 2 then @copy destDirId


  ###*
   * Set action type ('cut', 'copy', 'none')
   * @param type
   ###
  setActionType: (type) ->
    if type == @actionType
      @actionType = 0
    else
      switch type
        when 1, 2 then @actionType = type
        else @actionType = 0

  isCutSelected: ->
    @actionType == 1

  isCopySelected: ->
    @actionType == 2

  isNotSelected: ->
    @actionType == 0

  ###*
   * Shortcut to setActionType(1)
   ###
  setCut: ->
    @setActionType 1

  ###*
   * Shortcut to setActionType(2)
   ###
  setCopy: ->
    @setActionType 2

  ###*
   * Send files to server
   *
   * @param {int} destFolderId
   ###
  copy: (destFolderId) ->
    @spinnerService.show()

    @$http.put('/admin/filemanager/api/copy/selection', {
      destDirId: destFolderId
      files: @selectionService.getFilesIds()
      dirs: @selectionService.getFoldersIds()
    }, {
      headers:
        'X-Requested-With': 'XMLHttpRequest'
    })
      .then (response) =>
        @responseCallbackFunction response.data

  ###*
   * Open remove dialog
   * @param {Event} $event
   ###
  openRemoveDialog: ($event) ->
    @modalService.open $event, '/templates/selection_remove.html', {
      dirs: @selectionService.getFolders()
      files: @selectionService.getFiles()
    }

  ###*
   * Send request to server to remove files and folders
   * @param callback
   ###
  remove: (callback) ->
    @spinnerService.show()
    @$http.put('/admin/filemanager/api/delete/selection', {
      files: @selectionService.getFilesIds()
      dirs: @selectionService.getFoldersIds()
    }, {
      headers:
        'X-Requested-With': 'XMLHttpRequest'
    }).then (response) =>
      @responseCallbackFunction response.data

    callback()

  move: (destFolderId) ->
    @spinnerService.show();
    @$http.put('/admin/filemanager/api/move/selection', {
      destDirId: destFolderId
      files: @selectionService.getFilesIds()
      dirs: @selectionService.getFoldersIds()
    }, {
      headers:
        'X-Requested-With': 'XMLHttpRequest'
    }).then (response) =>
      @responseCallbackFunction response.data

  ###*
   * Response callback function call after copy, move and remove function
   *
   * @param {Object} response
   ###
  responseCallbackFunction: (response) ->
    @spinnerService.hide()
    if response.success
      @dirStructure.reload()

    @selectionService.clear()
    @setActionType 0