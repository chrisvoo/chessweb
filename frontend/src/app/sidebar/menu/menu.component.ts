import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth/auth.service';
import { Observable, of } from 'rxjs';
import {AsyncPipe} from '@angular/common';

@Component({
  selector: 'app-menu',
  imports: [
    RouterLink,
    AsyncPipe
  ],
  standalone: true,
  templateUrl: './menu.component.html',
  styleUrl: './menu.component.css'
})
export class MenuComponent {

  isAuthenticated: Observable<boolean>

  constructor(private authService: AuthService) {
    this.isAuthenticated = this.authService.isAuthenticated();
  }
}
