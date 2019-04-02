import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { FormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';
import { HttpModule } from '@angular/http';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import {
  MatIconModule, MatCardModule, MatButtonModule, MatButtonToggleModule,
  MatMenuModule, MatFormFieldModule, MatInputModule, MatSelectModule,
  MatCheckboxModule, MatRadioModule
} from '@angular/material';
import { DragulaModule } from 'ng2-dragula';
import { ClickOutsideModule } from 'ng-click-outside';
import { NguiAutoCompleteModule } from '@ngui/auto-complete';

import { AppRoutingModule } from './routing';
import { AppComponent } from './app.component';
import { AppService } from './app.service';
import { AddComponent } from './add/add.component';
import { UpdateComponent } from './update/update.component';
import { DeleteComponent } from './delete/delete.component';
import { LinkComponent } from './link/link.component';
import { LinkAdminComponent } from './link/admin.component';
import { LinkService } from './link/link.service';
import { EntityComponent } from './entity/entity.component';
import { EntityService } from './entity/entity.service';
import { LevelComponent } from './level/level.component';
import { LevelService } from './level/level.service';
import { IndexComponent } from './index/index.component';
import { ActivitiesComponent } from './activities/activities.component';
import { ActivitiesService } from './activities/activities.service';
import { ActivityComponent } from './activities/activity/activity.component';
import { ModuleComponent } from './activities/module/module.component';
import { ModuleService } from './activities/module/module.service';
import { AddActivityComponent } from './activities/add/add.component';
import { UpdateActivityComponent } from './activities/update/update.component';
import { PreviewActivityComponent } from './activities/preview/preview.component';
import { AddActivitiesBankComponent } from './activities/activities-bank/activities-bank.component';

@NgModule({
  imports: [
    BrowserModule,
    FormsModule,
    HttpClientModule,
    HttpModule,
    ClickOutsideModule,
    NguiAutoCompleteModule,
    BrowserAnimationsModule,
    MatIconModule,
    MatCardModule,
    MatButtonModule,
    MatButtonToggleModule,
    MatIconModule,
    MatMenuModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatCheckboxModule,
    MatRadioModule,
    DragulaModule,
    AppRoutingModule
  ],
  declarations: [
    AppComponent,
    LevelComponent,
    EntityComponent,
    AddComponent,
    UpdateComponent,
    DeleteComponent,
    LinkComponent,
    LinkAdminComponent,
    IndexComponent,
    ActivitiesComponent,
    ActivityComponent,
    ModuleComponent,
    AddActivityComponent  ,
    UpdateActivityComponent,
    PreviewActivityComponent,
    AddActivitiesBankComponent
  ],
  providers: [
    AppService,
    EntityService,
    LevelService,
    LinkService,
    ActivitiesService,
    ModuleService
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
