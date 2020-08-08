<div id="app_flow" class="css_app_list css_app_flow" v-show="show">
    <div>
        <el-tabs v-model="type.now" @tab-click="change_type">
            <el-tab-pane label="待处理" name="todo"><el-badge slot="label" :value="type.values.todo">待处理</el-badge></el-tab-pane>
            <el-tab-pane label="已处理" name="done"><el-badge slot="label" :value="type.values.done">已处理</el-badge></el-tab-pane>
            <el-tab-pane label="我发起的流程" name="mine"><el-badge slot="label" :value="type.values.mine">我发起的流程</el-badge></el-tab-pane>
        </el-tabs>
    </div>
    <div class="clear"></div>
    <div class="css_list_batch css_flow_batch">
        <div v-show="type.now=='mine'" class="new"><el-button size="small" type="primary" icon="el-icon-plus" @click="add">新增流程</el-button></div>
        <el-button size="small" icon="el-icon-refresh" @click="get_list">刷新</el-button>
    </div>
    <div class="clear"></div>

</div>