<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoatResource\Pages;
use App\Models\Boat;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class BoatResource extends Resource
{
    protected static ?string $model = Boat::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    
    protected static ?string $navigationGroup = 'Administration';
    
    protected static ?string $recordTitleAttribute = 'name';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Boat Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        
                        Forms\Components\TextInput::make('registration_number')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Boatman Assignment')
                    ->schema([
                        Forms\Components\Select::make('boatman_id')
                            ->label('Boatman')
                            ->options(function () {
                                // Mengambil user dengan role 'boatman'
                                return User::whereHas('roles', function ($query) {
                                    $query->where('name', 'boatman');
                                })
                                ->orWhere('role', 'boatman')
                                ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a boatman')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('registration_number')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('boatman.name')
                    ->label('Boatman')
                    ->searchable()
                    ->sortable()
                    ->default('No Boatman Assigned')
                    ->color(fn ($record) => $record->boatman_id ? 'success' : 'danger'),
                
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('boatman')
                    ->relationship('boatman', 'name')
                    ->label('Filter by Boatman'),
                    
                Tables\Filters\SelectFilter::make('creator')
                    ->relationship('creator', 'name')
                    ->label('Filter by Creator'),
                    
                Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned Boats')
                    ->query(fn (Builder $query): Builder => $query->whereNull('boatman_id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('assignBoatman')
                        ->label('Assign Boatman')
                        ->icon('heroicon-o-user')
                        ->form([
                            Forms\Components\Select::make('boatman_id')
                                ->label('Boatman')
                                ->options(function () {
                                    return User::whereHas('roles', function ($query) {
                                        $query->where('name', 'boatman');
                                    })
                                    ->orWhere('role', 'boatman')
                                    ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'boatman_id' => $data['boatman_id'],
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoats::route('/'),
            'create' => Pages\CreateBoat::route('/create'),
            'view' => Pages\ViewBoat::route('/{record}'),
            'edit' => Pages\EditBoat::route('/{record}/edit'),
        ];
    }
}