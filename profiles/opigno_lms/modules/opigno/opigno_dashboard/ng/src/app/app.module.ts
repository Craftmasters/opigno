import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { HttpClientModule } from '@angular/common/http';
import { HttpModule } from '@angular/http';
import { FormsModule } from '@angular/forms';
import { DragulaModule } from 'ng2-dragula';
import {
  MatIconModule, MatCardModule, MatButtonModule, MatButtonToggleModule,
  MatMenuModule, MatFormFieldModule, MatInputModule, MatSelectModule,
  MatCheckboxModule
} from '@angular/material';


import { AppComponent } from './app.component';
import { PanelComponent } from './panel/panel.component';
import { AppService } from './app.service';
import { SafeHtmlPipe } from './safe-html.pipe';
import { RunScriptsDirective } from './run-scripts.directive'


@NgModule({
  declarations: [
    AppComponent,
    PanelComponent,
    SafeHtmlPipe,
    RunScriptsDirective
  ],
  imports: [
    BrowserModule,
    HttpClientModule,
    HttpModule,
    DragulaModule,
    FormsModule,
    MatIconModule,
    MatCardModule,
    MatButtonModule,
    MatButtonToggleModule,
    MatIconModule,
    MatMenuModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatCheckboxModule
  ],
  providers: [
    AppService
  ],
  bootstrap: [
    AppComponent
  ]
})
export class AppModule { }
